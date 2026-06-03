<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class SinkholeDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return !$context->teamWon 
            && $context->impPerStart < $context->teamAverageGameImpPerStart
            && $context->playedSeconds >= $context->gameDurationSeconds * 0.6;
    }

    public function getValue(PlayerNarrativeContext $context): string
    {
        return number_format($context->impPerStart, 1) . ' IMP';
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return abs($context->impPerStart) * ($context->playedSeconds / 60);
    }

    public function getTier(): int
    {
        return 1;
    }

    public function isAdditive(): bool
    {
        return false;
    }

    public function getSlug(): string
    {
        return 'sinkhole';
    }
}
