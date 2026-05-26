<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class DailyScoringChampDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        $champStat = $context->playerStats->sortByDesc('points')->first();

        if ($champStat->points < 20) { // arbitrary threshold for a "champ"
            return [];
        }

        return [
            new DailyInsightDto(
                slug: 'daily_scoring_champ',
                title: 'Scoring Champion',
                playerId: $champStat->player_id,
                playerFullName: $champStat->player->full_name,
                value: $champStat->points . ' PTS',
                placeholders: [
                    '{player}' => $champStat->player->full_name,
                    '{value}' => $champStat->points . ' PTS',
                ]
            )
        ];
    }
}
