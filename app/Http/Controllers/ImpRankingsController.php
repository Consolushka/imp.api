<?php

namespace App\Http\Controllers;

use App\Dtos\RankedPlayerDto;
use App\Http\Requests\ImpRankingRequest;
use App\Http\Resources\RankedPlayerResource;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\Player;
use App\Models\Team;

class ImpRankingsController
{
    public function index(ImpRankingRequest $request)
    {
        $gameIds = Game::query()
            ->where('tournament_id', $request->getTournamentId())
            ->get('id');
        $playerStatIdsQuery = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds);
        if ($request->getTeamId()) {
            $playerStatIdsQuery->where('team_id', $request->getTeamId());
        }
        if ($request->getMinMinutes()) {
            $playerStatIdsQuery->where('played_seconds', '>=', $request->getMinMinutes() * 60);
        }
        if ($request->getMaxMinutes()) {
            $playerStatIdsQuery->where('played_seconds', '<=', $request->getMaxMinutes() * 60);
        }
        $playerStatRows = $playerStatIdsQuery->get(['player_id', 'team_id', 'id', 'played_seconds'])
            ->keyBy('id')->toArray();

        $playerImpsByCompositeKey = [];
        $playerSecondsByCompositeKey = [];

        try {
            // todo: оптимизировать что бы сразу брать всю стату
            $imps = (new ImpController())->calcImpForStatIds(array_keys($playerStatRows), [$request->getPer()], $request->useReliability());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'An error occurred during IMP calculation: ' . $e->getMessage()], 400);
        }

        foreach ($playerStatRows as $statId => $playerStat) {
            $compositeKey = $playerStat['player_id'] . '_' . $playerStat['team_id'];
            $playerImpsByCompositeKey[$compositeKey][] = $imps[$statId][$request->getPer()]->imp;
            $playerSecondsByCompositeKey[$compositeKey][] = $playerStat['played_seconds'];
        }

        uasort($playerImpsByCompositeKey, function ($a, $b) use ($request) {
            if ($request->getOrder() === 'asc') {
                return array_sum($a) / count($a) <=> array_sum($b) / count($b);
            } else {
                return array_sum($b) / count($b) <=> array_sum($a) / count($a);
            }
        });

        $playerImpsByCompositeKey = array_filter($playerImpsByCompositeKey, function ($item) use ($request) {
            return count($item) >= $request->getMinGames();
        });

        $playerImpsByCompositeKey = array_slice($playerImpsByCompositeKey, 0, $request->getLimit(), true);

        $position = 1;
        $leaderboard = [];

        $playerIds = [];
        $teamIds = [];
        foreach (array_keys($playerImpsByCompositeKey) as $compositeKey) {
            [$playerId, $teamId] = explode('_', $compositeKey);
            $playerIds[] = $playerId;
            $teamIds[] = $teamId;
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
                (int) (array_sum($playerSecondsByCompositeKey[$compositeKey]) / count($imps))
            );
        }

        return [
            'data' => RankedPlayerResource::collection($leaderboard),
        ];
    }
}
