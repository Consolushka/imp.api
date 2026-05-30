<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class CardioSessionDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->playedSeconds >= $context->gameDurationSeconds * 0.75
            && $context->points <= $context->maxGamePoints * 0.25
            && $context->rebounds <= $context->maxGameRebounds * 0.4
            && $context->assists <= $context->maxGameAssists * 0.4
            && abs($context->impPerStart) < 1.0;
    }

    public function getValue(PlayerNarrativeContext $context): string
    {
        return number_format($context->impPerStart, 1) . ' IMP';
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return (float) $context->playedSeconds;
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
        return 'cardio_session';
    }
}
