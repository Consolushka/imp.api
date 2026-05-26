<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class DailyMvpDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        $mvpStat = $context->playerStats->sortByDesc('imp')->first();

        return [
            new DailyInsightDto(
                slug: 'daily_mvp',
                title: 'Player of the Day',
                playerId: $mvpStat->player_id,
                playerFullName: $mvpStat->player->full_name,
                value: number_format($mvpStat->imp, 1) . ' IMP',
                placeholders: [
                    '{player}' => $mvpStat->player->full_name,
                    '{value}' => number_format($mvpStat->imp, 1) . ' IMP',
                ]
            )
        ];
    }
}
