<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class SniperDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->playedSeconds >= $context->gameDurationSeconds * 0.4
            && $context->points >= 15
            && $context->fieldGoalsPercentage >= 0.65;
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return $context->fieldGoalsPercentage * ($context->playedSeconds / 60);
    }

    public function getTier(): int
    {
        return 2;
    }

    public function isAdditive(): bool
    {
        return false;
    }

    public function getSlug(): string
    {
        return 'sniper';
    }
}
