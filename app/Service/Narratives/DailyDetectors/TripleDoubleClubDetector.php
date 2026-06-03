<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class TripleDoubleClubDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        $club = [];
        
        foreach ($context->playerStats as $stat) {
            $categories = 0;
            if ($stat->points >= 10) $categories++;
            if ($stat->rebounds >= 10) $categories++;
            if ($stat->assists >= 10) $categories++;
            if ($stat->steals >= 10) $categories++;
            if ($stat->blocks >= 10) $categories++;

            if ($categories >= 3) {
                $club[] = new DailyInsightDto(
                    slug: 'triple_double_club',
                    title: 'Triple-Double Club',
                    playerId: $stat->player_id,
                    playerFullName: $stat->player->full_name,
                    value: "Triple-Double",
                    placeholders: [
                        '{player}' => $stat->player->full_name,
                        '{points}' => (string)$stat->points,
                        '{rebounds}' => (string)$stat->rebounds,
                        '{assists}' => (string)$stat->assists,
                    ]
                );
            }
        }

        return $club;
    }
}
