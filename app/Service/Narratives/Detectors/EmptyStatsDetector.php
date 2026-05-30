<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class EmptyStatsDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->teamWon === false
            && $context->points >= $context->maxGamePoints * 0.6
            && $context->impPerStart < 0;
    }

    public function getValue(PlayerNarrativeContext $context): string
    {
        return $context->points . ' PTS';
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return $context->points * abs($context->impPerStart ?: 1);
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
        return 'empty_stats';
    }
}
