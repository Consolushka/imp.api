<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class GlassEaterDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        $topStat = $context->playerStats->sortByDesc('rebounds')->first();

        if ($topStat->rebounds >= 12) {
            return [
                new DailyInsightDto(
                    slug: 'glass_eater',
                    title: 'Glass Eater',
                    playerId: $topStat->player_id,
                    playerFullName: $topStat->player->full_name,
                    value: "{$topStat->rebounds} REB",
                    placeholders: [
                        '{player}' => $topStat->player->full_name,
                        '{value}' => (string)$topStat->rebounds,
                    ]
                )
            ];
        }

        return [];
    }
}
