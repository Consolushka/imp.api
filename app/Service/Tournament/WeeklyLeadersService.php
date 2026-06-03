<?php

namespace App\Service\Tournament;

use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Service\Imp\ImpService;
use Carbon\Carbon;

class WeeklyLeadersService
{
    public function __construct(
        private readonly ImpService $impService
    ) {}

    /**
     * @param array<int> $tournamentIds
     * @param Carbon|null $referenceDate
     * @return array
     */
    public function calculateLeaders(array $tournamentIds = [], ?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?: now();
        $start = $referenceDate->copy()->startOfWeek();
        $end = $referenceDate->copy()->endOfWeek();

        $gameIdsQuery = Game::query()
            ->whereBetween('scheduled_at', [$start, $end]);

        if (!empty($tournamentIds)) {
            $gameIdsQuery->whereIn('tournament_id', $tournamentIds);
        }

        $gameIds = $gameIdsQuery->pluck('id');

        if ($gameIds->isEmpty()) {
            return [];
        }

        $playerStats = GameTeamPlayerStat::with('player')
            ->whereIn('game_id', $gameIds)
            ->get();

        if ($playerStats->isEmpty()) {
            return [];
        }

        $statIds = $playerStats->pluck('id')->toArray();
        $imps = $this->impService->calcImpForStatIds($statIds, ['fullGame']);

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

        return $result;
    }
}
