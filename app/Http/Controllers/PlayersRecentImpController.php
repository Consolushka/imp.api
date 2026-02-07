<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayersRecentImpRequest;
use App\Http\Resources\PlayersRecentImpResource;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\PersEnum;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PlayersRecentImpController extends Controller
{
    public function index(PlayersRecentImpRequest $request)
    {
        $playerIds = $request->getPlayerIds();
        $tournamentId = $request->getTournamentId();
        $teamId = $request->getTeamId();
        $per = PersEnum::from($request->getPer());
        $offset = $request->getOffset();
        $limit = $request->getLimit();

        $data = [];

        foreach ($playerIds as $playerId) {
            $baseQuery = DB::table('game_team_player_stats')
                ->select(
                    'game_team_player_stats.game_id',
                    'game_team_player_stats.team_id',
                    'game_team_player_stats.played_seconds',
                    'game_team_player_stats.plus_minus',
                    'game_team_stats.final_differential',
                    'games.duration',
                    'games.scheduled_at'
                )
                ->leftJoin('game_team_stats', function ($join) {
                    $join->on('game_team_stats.game_id', '=', 'game_team_player_stats.game_id')
                        ->on('game_team_stats.team_id', '=', 'game_team_player_stats.team_id');
                })
                ->leftJoin('games', 'games.id', '=', 'game_team_player_stats.game_id')
                ->where('games.tournament_id', $tournamentId)
                ->where('game_team_player_stats.player_id', $playerId);

            if ($teamId !== null) {
                $baseQuery->where('game_team_player_stats.team_id', $teamId);
            }

            $total = $baseQuery->clone()->count();

            $rows = $baseQuery
                ->orderBy('games.scheduled_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $games = [];
            foreach ($rows as $row) {
                $duration = (int) $row->duration;
                if ($duration === 0) {
                    $imp = null;
                } else {
                    $imp = ImpCalculator::evaluatePer(
                        (int) $row->played_seconds,
                        (int) $row->plus_minus,
                        (int) $row->final_differential,
                        $duration,
                        $per
                    );
                }

                $games[] = [
                    'game_id' => (int) $row->game_id,
                    'scheduled_at' => $row->scheduled_at,
                    'imp' => $imp,
                ];
            }

            $data[] = [
                'player_id' => $playerId,
                'games' => $games,
                'meta' => [
                    'total' => $total,
                    'offset' => $offset,
                    'limit' => $limit,
                ],
            ];
        }

        return [
            'data' => PlayersRecentImpResource::collection($data),
            'meta' => [
                'per' => $request->getPer(),
            ],
        ];
    }
}
