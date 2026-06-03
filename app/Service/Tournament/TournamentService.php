<?php

namespace App\Service\Tournament;

use App\Models\GameTeamStat;
use App\Models\GameTeamPlayerStat;
use App\Models\Player;
use App\Models\Tournament;
use App\Service\Imp\ImpService;

class TournamentService
{
    public function __construct(
        private readonly ImpService $impService
    ) {}

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
