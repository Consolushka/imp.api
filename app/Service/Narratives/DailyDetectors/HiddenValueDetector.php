<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class HiddenValueDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        // High IMP with very low points
        $topStat = $context->playerStats
            ->filter(fn($s) => $s->points < 10 && $s->played_seconds >= 900)
            ->sortByDesc('imp')
            ->first();

        if ($topStat && $topStat->imp > 5.0) {
            return [
                new DailyInsightDto(
                    slug: 'hidden_value',
                    title: 'Hidden Value',
                    playerId: $topStat->player_id,
                    playerFullName: $topStat->player->full_name,
                    value: number_format($topStat->imp, 1) . ' IMP',
                    placeholders: [
                        '{player}' => $topStat->player->full_name,
                        '{value}' => number_format($topStat->imp, 1),
                        '{points}' => (string)$topStat->points,
                    ]
                )
            ];
        }

        return [];
    }
}
