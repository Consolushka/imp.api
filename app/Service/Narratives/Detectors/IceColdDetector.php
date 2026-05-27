<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class IceColdDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->playedSeconds >= $context->gameDurationSeconds * 0.4
            && $context->fieldGoalsPercentage > 0
            && $context->fieldGoalsPercentage < 0.33;
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return (0.33 - $context->fieldGoalsPercentage) * ($context->playedSeconds / 60);
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
        return 'ice_cold';
    }
}
