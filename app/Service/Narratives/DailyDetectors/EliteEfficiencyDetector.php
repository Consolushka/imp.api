<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class EliteEfficiencyDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        // Find best FG% with at least 10 attempts
        // Assuming models have field_goals_attempted and field_goals_percentage
        $topStat = $context->playerStats
            ->filter(fn($s) => ($s->field_goals_attempted ?? 0) >= 10)
            ->sortByDesc('field_goals_percentage')
            ->first();

        if ($topStat && ($topStat->field_goals_percentage ?? 0) >= 0.7) {
            $percent = (int)($topStat->field_goals_percentage * 100);
            return [
                new DailyInsightDto(
                    slug: 'elite_efficiency',
                    title: 'Elite Efficiency',
                    playerId: $topStat->player_id,
                    playerFullName: $topStat->player->full_name,
                    value: "{$percent}% FG",
                    placeholders: [
                        '{player}' => $topStat->player->full_name,
                        '{value}' => "{$percent}%",
                    ]
                )
            ];
        }

        return [];
    }
}
