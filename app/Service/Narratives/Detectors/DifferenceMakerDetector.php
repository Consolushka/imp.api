<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class DifferenceMakerDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->teamWon 
            && $context->impPerStart > $context->teamAverageGameImpPerStart
            && (
                $context->points >= $context->maxGamePoints * 0.8 ||
                $context->impPerStart >= $context->maxGameImp * 0.8
            );
    }

    public function getValue(PlayerNarrativeContext $context): string
    {
        return number_format($context->impPerStart, 1) . ' IMP';
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return $context->impPerStart;
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
        return 'difference_maker';
    }
}
