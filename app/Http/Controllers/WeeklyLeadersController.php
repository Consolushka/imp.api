<?php

namespace App\Http\Controllers;

use App\Http\Resources\WeeklyLeaderResource;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WeeklyLeadersController extends Controller
{
    public function index(Request $request)
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $tournamentIds = $request->get('tournamentIds');
        if (is_string($tournamentIds)) {
            $tournamentIds = explode(',', $tournamentIds);
        }

        $gameIdsQuery = Game::query()
            ->whereBetween('scheduled_at', [$start, $end]);

        if (!empty($tournamentIds)) {
            $gameIdsQuery->whereIn('tournament_id', (array)$tournamentIds);
        }

        $gameIds = $gameIdsQuery->pluck('id');

        if ($gameIds->isEmpty()) {
            return [
                'data' => []
            ];
        }

        $playerStats = GameTeamPlayerStat::with('player')
            ->whereIn('game_id', $gameIds)
            ->get();

        if ($playerStats->isEmpty()) {
            return [
                'data' => []
            ];
        }

        $statIds = $playerStats->pluck('id')->toArray();
        $imps = (new ImpController())->calcImpForStatIds($statIds, ['fullGame']);

        $playerAverages = [];

        foreach ($playerStats as $stat) {
            $playerId = $stat->player_id;
            if (!isset($playerAverages[$playerId])) {
                $playerAverages[$playerId] = [
                    'full_name' => $stat->player->full_name,
                    'pts'       => [],
                    'ast'       => [],
                    'reb'       => [],
                    'imp'       => [],
                ];
            }

            $playerAverages[$playerId]['pts'][] = $stat->points;
            $playerAverages[$playerId]['ast'][] = $stat->assists;
            $playerAverages[$playerId]['reb'][] = $stat->rebounds;
            $playerAverages[$playerId]['imp'][] = $imps[$stat->id]['fullGame']->imp ?? 0;
        }

        $categories = ['points' => 'pts', 'assists' => 'ast', 'rebounds' => 'reb', 'imp' => 'imp'];
        $result = [];

        foreach ($categories as $label => $key) {
            $bestPlayer = null;
            $maxValue = -INF;

            foreach ($playerAverages as $playerId => $data) {
                $avg = array_sum($data[$key]) / count($data[$key]);
                if ($avg > $maxValue) {
                    $maxValue = $avg;
                    $bestPlayer = $data['full_name'];
                }
            }

            if ($bestPlayer) {
                $result[] = [
                    'category'         => $label,
                    'player_full_name' => $bestPlayer,
                    'value'            => $maxValue,
                ];
            }
        }

        return WeeklyLeaderResource::collection($result);
    }
}
