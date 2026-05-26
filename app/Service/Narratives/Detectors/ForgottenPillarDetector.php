<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class ForgottenPillarDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->teamWon === false
            && $context->playedSeconds >= $context->gameDurationSeconds * 0.6
            && $context->impPerStart >= $context->maxGameImp * 0.7
            && $context->points <= $context->maxGamePoints * 0.4;
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return $context->impPerStart * ($context->playedSeconds / 60);
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
        return 'forgotten_pillar';
    }
}
