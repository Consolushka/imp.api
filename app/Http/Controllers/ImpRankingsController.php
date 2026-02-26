<?php

namespace App\Http\Controllers;

use App\Dtos\RankedPlayerDto;
use App\Http\Requests\ImpRankingRequest;
use App\Http\Resources\RankedPlayerResource;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\Player;

class ImpRankingsController
{
    public function index(ImpRankingRequest $request)
    {
        $gameIds = Game::query()
            ->where('tournament_id', $request->getTournamentId())
            ->get('id');
        $playerStatIds = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds);
        if ($request->getTeamId()) {
            $playerStatIds->where('team_id', $request->getTeamId());
        }
        if ($request->getMinMinutes()) {
            $playerStatIds->where('played_seconds', '>=', $request->getMinMinutes() * 60);
        }
        if ($request->getMaxMinutes()) {
            $playerStatIds->where('played_seconds', '<=', $request->getMaxMinutes() * 60);
        }
        $playerStatIds = $playerStatIds->get(['player_id', 'id'])
            ->groupBy('id')->toArray();

        $playerImpsByPlayerId = [];

        try {
            // todo: оптимизировать что бы сразу брать всю стату
            $imps = (new ImpController())->calcImpForStatIds(array_keys($playerStatIds), [$request->getPer()], $request->useReliability());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'An error occurred during IMP calculation: ' . $e->getMessage()], 400);
        }

        foreach ($playerStatIds as $playerId => $playerStat) {
            $playerImpsByPlayerId[$playerStat[0]['player_id']][] = $imps[$playerId][$request->getPer()]->imp;
        }

        uasort($playerImpsByPlayerId, function ($a, $b) use ($request) {
            if ($request->getOrder() === 'asc') {
                return array_sum($a) / count($a) <=> array_sum($b) / count($b);
            } else {
                return array_sum($b) / count($b) <=> array_sum($a) / count($a);
            }
        });

        $playerImpsByPlayerId = array_filter($playerImpsByPlayerId, function ($item) use ($request) {
            return count($item) >= $request->getMinGames();
        });

        $position = 1;
        $leaderboard = [];
        $playerModels = Player::query()->whereIn('id', array_keys($playerImpsByPlayerId))->get()->groupBy('id');
        foreach (array_slice($playerImpsByPlayerId, 0, $request->getLimit(), true) as $playerId => $imps) {
            $leaderboard[] = new RankedPlayerDto(
                $position++,
                $playerId,
                $playerModels[$playerId][0],
                count($imps),
                array_sum($imps) / count($imps)
            );
        }

        return [
            'data' => RankedPlayerResource::collection($leaderboard),
        ];
    }
}
