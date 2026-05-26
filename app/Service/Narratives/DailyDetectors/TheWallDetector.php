<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class TheWallDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        $topStat = $context->playerStats->sortByDesc(fn($s) => $s->blocks + $s->steals)->first();
        $defensiveActions = $topStat->blocks + $topStat->steals;

        if ($defensiveActions >= 5) {
            return [
                new DailyInsightDto(
                    slug: 'the_wall',
                    title: 'The Wall',
                    playerId: $topStat->player_id,
                    playerFullName: $topStat->player->full_name,
                    value: "{$defensiveActions} Stocks",
                    placeholders: [
                        '{player}' => $topStat->player->full_name,
                        '{value}' => (string)$defensiveActions,
                        '{blocks}' => (string)$topStat->blocks,
                        '{steals}' => (string)$topStat->steals,
                    ]
                )
            ];
        }

        return [];
    }
}
