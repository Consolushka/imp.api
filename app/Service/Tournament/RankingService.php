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
     * @param RankingFilters $filters
     * @return array<RankedPlayerDto>
     */
    public function calculate(RankingFilters $filters): array
    {
        $gameIds = Game::query()
            ->where('tournament_id', $filters->tournamentId)
            ->pluck('id');

        $playerStatIdsQuery = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds);

        if ($filters->teamId) {
            $playerStatIdsQuery->where('team_id', $filters->teamId);
        }

        if ($filters->minMinutes) {
            $playerStatIdsQuery->where('played_seconds', '>=', $filters->minMinutes * 60);
        } else {
            $playerStatIdsQuery->where('played_seconds', '>', 0);
        }

        $playerStatRows = $playerStatIdsQuery->get(['player_id', 'team_id', 'id', 'played_seconds'])
            ->keyBy('id')
            ->toArray();

        $playerImpsByCompositeKey = [];
        $playerSecondsByCompositeKey = [];

        $imps = $this->impService->calcImpForStatIds(
            array_keys($playerStatRows),
            [$filters->per],
            $filters->useReliability
        );

        foreach ($playerStatRows as $statId => $playerStat) {
            $compositeKey = $playerStat['player_id'] . '_' . $playerStat['team_id'];
            $playerImpsByCompositeKey[$compositeKey][] = $imps[$statId][$filters->per]->imp;
            $playerSecondsByCompositeKey[$compositeKey][] = $playerStat['played_seconds'];
        }

        $playerImpsByCompositeKey = array_filter($playerImpsByCompositeKey, function ($item) use ($filters) {
            return count($item) >= $filters->minGames;
        });

        if ($filters->avgMinutes) {
            $playerImpsByCompositeKey = array_filter($playerImpsByCompositeKey, function ($imps, $compositeKey) use ($filters, $playerSecondsByCompositeKey) {
                $avgSeconds = array_sum($playerSecondsByCompositeKey[$compositeKey]) / count($imps);
                return $avgSeconds >= ($filters->avgMinutes * 60);
            }, ARRAY_FILTER_USE_BOTH);
        }

        uasort($playerImpsByCompositeKey, function ($a, $b) use ($filters) {
            $avgA = array_sum($a) / count($a);
            $avgB = array_sum($b) / count($b);

            if ($filters->order === 'asc') {
                return $avgA <=> $avgB;
            } else {
                return $avgB <=> $avgA;
            }
        });

        $position = 1;
        $leaderboard = [];

        $playerIds = [];
        $teamIds = [];
        foreach (array_keys($playerImpsByCompositeKey) as $compositeKey) {
            [$playerId, $teamId] = explode('_', $compositeKey);
            $playerIds[] = (int)$playerId;
            $teamIds[] = (int)$teamId;
        }

        $playerModels = Player::query()->whereIn('id', array_unique($playerIds))->get()->keyBy('id');
        $teamModels = Team::query()->whereIn('id', array_unique($teamIds))->get()->keyBy('id');

        foreach ($playerImpsByCompositeKey as $compositeKey => $imps) {
            [$playerId, $teamId] = explode('_', $compositeKey);
            $leaderboard[] = new RankedPlayerDto(
                $position++,
                (int)$playerId,
                $playerModels[$playerId],
                $teamModels[$teamId]->alias ?? '',
                count($imps),
                array_sum($imps) / count($imps),
                (int)(array_sum($playerSecondsByCompositeKey[$compositeKey]) / count($imps))
            );
        }

        return $leaderboard;
    }
}
