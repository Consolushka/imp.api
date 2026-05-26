<?php

namespace App\Service\Tournament;

use App\Dtos\DailyContext;
use App\Dtos\PlayerOfTheDayDto;
use App\Models\Game;
use App\Models\GameTeamStat;
use App\Models\GameTeamPlayerStat;
use App\Models\Player;
use App\Models\Tournament;
use App\Service\Imp\ImpService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TournamentService
{
    public function __construct(
        private readonly ImpService $impService
    ) {}

    public function getDailyContext(int $tournamentId, Carbon $date): DailyContext
    {
        $games = Game::with(['gameTeamStats'])
            ->where('tournament_id', $tournamentId)
            ->whereDate('scheduled_at', $date->toDateString())
            ->get();

        if ($games->isEmpty()) {
            return new DailyContext(collect(), collect());
        }

        $gameIds = $games->pluck('id');

        $playerStats = GameTeamPlayerStat::with(['player', 'team'])
            ->whereIn('game_id', $gameIds)
            ->get();

        if ($playerStats->isNotEmpty()) {
            $statIds = $playerStats->pluck('id')->toArray();
            $imps = $this->impService->calcImpForStatIds($statIds, ['fullGame'], true);

            foreach ($playerStats as $stat) {
                $stat->imp = $imps[$stat->id]['fullGame']->imp ?? 0;
            }
        }

        return new DailyContext($games, $playerStats);
    }

    public function calculatePlayersOfTheDay(int $tournamentId, Carbon $date, int $limit = 5, bool $useReliability = true): Collection
    {
        $games = Game::where('tournament_id', $tournamentId)
            ->whereDate('scheduled_at', $date->toDateString())
            ->get();

        if ($games->isEmpty()) {
            return collect();
        }

        $gameIds = $games->pluck('id');

        $playerStats = GameTeamPlayerStat::with(['player', 'team'])
            ->whereIn('game_id', $gameIds)
            ->get();

        if ($playerStats->isEmpty()) {
            return collect();
        }

        $statIds = $playerStats->pluck('id')->toArray();
        $imps = $this->impService->calcImpForStatIds($statIds, ['fullGame'], $useReliability);

        return $playerStats->map(function (GameTeamPlayerStat $stat) use ($imps) {
            $imp = $imps[$stat->id]['fullGame']->imp ?? 0;

            return new PlayerOfTheDayDto(
                id: (int)$stat->player_id,
                fullName: $stat->player->full_name,
                teamAlias: $stat->team->alias,
                playedSeconds: (int)$stat->played_seconds,
                pts: (int)$stat->points,
                reb: (int)$stat->rebounds,
                ast: (int)$stat->assists,
                imp: (float)$imp
            );
        })->sortByDesc('imp')->take($limit)->values();
    }

    public function getBestPlayerFullName(Tournament $tournament): string
    {
        $gameIds = $tournament->games()->pluck('id');
        if ($gameIds->isEmpty() || !$tournament->regulation_duration) {
            return 'N/A';
        }

        $minPlayedSeconds = $tournament->regulation_duration * 60 * 0.4;

        $teamGamesCount = GameTeamStat::query()
            ->whereIn('game_id', $gameIds)
            ->groupBy('team_id')
            ->selectRaw('team_id, count(*) as count')
            ->pluck('count', 'team_id');

        $playerStats = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds)
            ->where('played_seconds', '>=', $minPlayedSeconds)
            ->get(['id', 'player_id', 'team_id']);

        $playerGroups = $playerStats->groupBy(function ($item) {
            return $item->player_id . '_' . $item->team_id;
        });

        $validGroups = [];
        foreach ($playerGroups as $key => $stats) {
            [$playerId, $teamId] = explode('_', $key);
            $gamesPlayed = $stats->count();
            $totalTeamGames = $teamGamesCount[$teamId] ?? 0;

            if ($totalTeamGames > 0 && $gamesPlayed >= ceil($totalTeamGames * 0.3)) {
                $validGroups[$key] = [
                    'player_id' => (int)$playerId,
                    'stat_ids' => $stats->pluck('id')->toArray(),
                ];
            }
        }

        if (empty($validGroups)) {
            return 'N/A';
        }

        $allStatIds = [];
        foreach ($validGroups as $group) {
            $allStatIds = array_merge($allStatIds, $group['stat_ids']);
        }

        $imps = $this->impService->calcImpForStatIds($allStatIds, ['fullGame']);

        $bestPlayerId = null;
        $maxAvgImp = -INF;

        foreach ($validGroups as $key => $group) {
            $sumImp = 0;
            foreach ($group['stat_ids'] as $statId) {
                $sumImp += $imps[$statId]['fullGame']->imp ?? 0;
            }
            $avgImp = $sumImp / count($group['stat_ids']);

            if ($avgImp > $maxAvgImp) {
                $maxAvgImp = $avgImp;
                $bestPlayerId = $group['player_id'];
            }
        }

        if ($bestPlayerId) {
            $player = Player::find($bestPlayerId);
            return $player ? $player->full_name : 'N/A';
        }

        return 'N/A';
    }
}
