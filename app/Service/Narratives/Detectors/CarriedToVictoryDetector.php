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
            && $context->impPerStart < $context->teamAverageGameImpPerStart;
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
