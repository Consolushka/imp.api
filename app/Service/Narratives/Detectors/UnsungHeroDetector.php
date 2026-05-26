<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class UnsungHeroDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->teamWon === true
            && $context->points < $context->maxGamePoints * 0.5
            && $context->impPerStart >= $context->maxGameImp * 0.8;
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return $context->impPerStart;
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
        return 'unsung_hero';
    }
}
