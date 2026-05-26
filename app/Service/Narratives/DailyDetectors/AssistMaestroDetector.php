<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class AssistMaestroDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->playerStats->isEmpty()) {
            return [];
        }

        $topStat = $context->playerStats->sortByDesc('assists')->first();

        if ($topStat->assists >= 10) {
            return [
                new DailyInsightDto(
                    slug: 'assist_maestro',
                    title: 'Assist Maestro',
                    playerId: $topStat->player_id,
                    playerFullName: $topStat->player->full_name,
                    value: "{$topStat->assists} AST",
                    placeholders: [
                        '{player}' => $topStat->player->full_name,
                        '{value}' => (string)$topStat->assists,
                    ]
                )
            ];
        }

        return [];
    }
}
