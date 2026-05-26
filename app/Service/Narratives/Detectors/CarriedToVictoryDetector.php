<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class CarriedToVictoryDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->teamWon === true
            && $context->playedSeconds >= $context->gameDurationSeconds * 0.7
            && $context->impPerStart < 0
            && $context->impPerStart < $context->teamAverageGameImpPerStart
            && $context->points <= $context->maxGamePoints * 0.4;
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
        return 'carried_to_victory';
    }
}
