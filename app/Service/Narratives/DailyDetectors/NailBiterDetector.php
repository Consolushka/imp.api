<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Models\GameTeamStat;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class NailBiterDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        if ($context->games->isEmpty()) {
            return [];
        }

        $bestGame = null;
        $minDiff = 999;

        foreach ($context->games as $game) {
            // Find differential for this game
            $diff = $game->gameTeamStats->map(fn(GameTeamStat $s) => abs($s->final_differential))->max() ?: 999;
            
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $bestGame = $game;
            }
        }

        if ($bestGame && $minDiff <= 3) {
            return [
                new DailyInsightDto(
                    slug: 'nail_biter',
                    title: 'Game of the Day',
                    gameId: $bestGame->id,
                    value: "{$minDiff} PTS diff",
                    placeholders: [
                        '{game}' => $bestGame->title,
                        '{value}' => "{$minDiff} PTS",
                    ]
                )
            ];
        }

        return [];
    }
}
