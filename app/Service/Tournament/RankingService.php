<?php

namespace App\Service\Tournament;

use App\Dtos\RankedPlayerDto;
use App\Http\Requests\ImpRankingRequest;
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
     * @param ImpRankingRequest $request
     * @return array<RankedPlayerDto>
     */
    public function calculate(ImpRankingRequest $request): array
    {
        $gameIds = Game::query()
            ->where('tournament_id', $request->getTournamentId())
            ->pluck('id');

        $playerStatIdsQuery = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds);

        if ($request->getTeamId()) {
            $playerStatIdsQuery->where('team_id', $request->getTeamId());
        }

        if ($request->getMinMinutes()) {
            $playerStatIdsQuery->where('played_seconds', '>=', $request->getMinMinutes() * 60);
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
            [$request->getPer()],
            $request->useReliability()
        );

        foreach ($playerStatRows as $statId => $playerStat) {
            $compositeKey = $playerStat['player_id'] . '_' . $playerStat['team_id'];
            $playerImpsByCompositeKey[$compositeKey][] = $imps[$statId][$request->getPer()]->imp;
            $playerSecondsByCompositeKey[$compositeKey][] = $playerStat['played_seconds'];
        }

        $playerImpsByCompositeKey = array_filter($playerImpsByCompositeKey, function ($item) use ($request) {
            return count($item) >= $request->getMinGames();
        });

        if ($request->getAvgMinutes()) {
            $playerImpsByCompositeKey = array_filter($playerImpsByCompositeKey, function ($imps, $compositeKey) use ($request, $playerSecondsByCompositeKey) {
                $avgSeconds = array_sum($playerSecondsByCompositeKey[$compositeKey]) / count($imps);
                return $avgSeconds >= ($request->getAvgMinutes() * 60);
            }, ARRAY_FILTER_USE_BOTH);
        }

        uasort($playerImpsByCompositeKey, function ($a, $b) use ($request) {
            $avgA = array_sum($a) / count($a);
            $avgB = array_sum($b) / count($b);

            if ($request->getOrder() === 'asc') {
                return $avgA <=> $avgB;
            } else {
                return $avgB <=> $avgA;
            }
        });

        $playerImpsByCompositeKey = array_slice($playerImpsByCompositeKey, 0, $request->getLimit(), true);

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
