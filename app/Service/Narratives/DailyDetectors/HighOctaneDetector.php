<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class HighOctaneDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        $bestGame = null;
        $maxTotal = 0;

        foreach ($context->games as $game) {
            $total = $game->gameTeamStats->sum('points');
            if ($total > $maxTotal) {
                $maxTotal = $total;
                $bestGame = $game;
            }
        }

        if ($bestGame && $maxTotal >= 220) { // Threshold for high scoring
            return [
                new DailyInsightDto(
                    slug: 'high_octane_battle',
                    title: 'High-Octane Battle',
                    gameId: $bestGame->id,
                    value: "{$maxTotal} Total PTS",
                    placeholders: [
                        '{game}' => $bestGame->title,
                        '{value}' => (string)$maxTotal,
                    ]
                )
            ];
        }

        return [];
    }
}
