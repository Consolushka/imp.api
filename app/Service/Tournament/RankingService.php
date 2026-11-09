<?php

namespace App\Service\Tournament;

use App\Dtos\RankedPlayerDto;
use App\Dtos\RankingFilters;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\Player;
use App\Models\Team;
use App\Service\Imp\ImpService;

class RankingService
{
    public function __construct(
        private readonly ImpService $impService
    ) {}

    /**
     * IMP по каждой строке статистики турнира: из этого набора в памяти собирается рейтинг для любых фильтров.
     * Массивы, а не объекты: набор целиком лежит в кеше, объекты раздули бы запись в разы.
     *
     * @return list<array{int, int, int, float}> игрок, команда, сыгранные секунды, IMP — по возрастанию id строки
     */
    public function calculateBase(int $tournamentId, string $per, bool $useReliability): array
    {
        $gameIds = Game::query()
            ->where('tournament_id', $tournamentId)
            ->pluck('id');

        // Сумма дробных чисел зависит от порядка сложения: без явного порядка среднее игрока менялось бы вместе с порядком строк в базе.
        $statRows = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds)
            ->where('played_seconds', '>', 0)
            ->orderBy('id')
            ->toBase()
            ->get(['id', 'player_id', 'team_id', 'played_seconds']);

        $imps = $this->impService->calcImpForStatIds($statRows->pluck('id')->all(), [$per], $useReliability);

        return $statRows
            ->map(fn (object $row) => [(int) $row->player_id, (int) $row->team_id, (int) $row->played_seconds, $imps[$row->id][$per]->imp])
            ->all();
    }

    /**
     * @param  list<array{int, int, int, float}>  $rows  результат calculateBase
     * @return list<array{position: int, playerId: int, teamId: int, games: int, avgImp: float, avgPlayedSeconds: int}>
     */
    public function selectRanked(array $rows, RankingFilters $filters): array
    {
        $impsByKey = [];
        $secondsByKey = [];

        // Команда и минуты отсекают отдельные игры ещё до группировки: от них зависят и число игр, и среднее.
        foreach ($rows as [$playerId, $teamId, $seconds, $imp]) {
            if ($filters->teamId && $teamId !== $filters->teamId) {
                continue;
            }

            if ($filters->minMinutes && $seconds < $filters->minMinutes * 60) {
                continue;
            }

            $key = $playerId.'_'.$teamId;
            $impsByKey[$key][] = $imp;
            $secondsByKey[$key][] = $seconds;
        }

        $ranked = [];

        foreach ($impsByKey as $key => $imps) {
            $games = count($imps);
            $avgSeconds = array_sum($secondsByKey[$key]) / $games;

            if ($games < $filters->minGames || ($filters->avgMinutes && $avgSeconds < $filters->avgMinutes * 60)) {
                continue;
            }

            [$playerId, $teamId] = explode('_', $key);
            $ranked[$key] = [
                'playerId' => (int) $playerId,
                'teamId' => (int) $teamId,
                'games' => $games,
                'avgImp' => array_sum($imps) / $games,
                'avgPlayedSeconds' => (int) $avgSeconds,
            ];
        }

        // Сортировка ниже не меняет взаимный порядок равных, поэтому при равном среднем места определяет этот ключ.
        ksort($ranked, SORT_STRING);

        uasort($ranked, fn (array $a, array $b) => $filters->order === 'asc'
            ? $a['avgImp'] <=> $b['avgImp']
            : $b['avgImp'] <=> $a['avgImp']);

        $position = 1;
        $result = [];

        foreach ($ranked as $entry) {
            $result[] = ['position' => $position++] + $entry;
        }

        return $result;
    }

    /**
     * @param  list<array{position: int, playerId: int, teamId: int, games: int, avgImp: float, avgPlayedSeconds: int}>  $page  строки отдаваемой страницы
     * @return list<RankedPlayerDto>
     */
    public function hydrate(array $page): array
    {
        $players = Player::query()->whereIn('id', array_column($page, 'playerId'))->get()->keyBy('id');
        $teamAliases = Team::query()->whereIn('id', array_column($page, 'teamId'))->pluck('alias', 'id');

        return array_map(fn (array $entry) => new RankedPlayerDto(
            $entry['position'],
            $entry['playerId'],
            $players[$entry['playerId']],
            $teamAliases[$entry['teamId']] ?? '',
            $entry['games'],
            $entry['avgImp'],
            $entry['avgPlayedSeconds'],
        ), $page);
    }
}
