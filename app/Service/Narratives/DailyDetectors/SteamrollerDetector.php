<?php

namespace App\Service\Narratives\DailyDetectors;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Models\GameTeamStat;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class SteamrollerDetector implements DailyInsightDetector
{
    public function analyze(DailyContext $context): array
    {
        $bestGame = null;
        $maxDiff = 0;

        foreach ($context->games as $game) {
            $diff = $game->gameTeamStats->map(fn(GameTeamStat $s) => abs($s->final_differential))->max() ?: 0;
            if ($diff > $maxDiff) {
                $maxDiff = $diff;
                $bestGame = $game;
            }
        }

        if ($bestGame && $maxDiff >= 25) {
            return [
                new DailyInsightDto(
                    slug: 'the_steamroller',
                    title: 'The Steamroller',
                    gameId: $bestGame->id,
                    value: "{$maxDiff} PTS diff",
                    placeholders: [
                        '{game}' => $bestGame->title,
                        '{value}' => "{$maxDiff} PTS",
                    ]
                )
            ];
        }

        return [];
    }
}
