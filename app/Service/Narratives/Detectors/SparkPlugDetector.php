<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class SparkPlugDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        return $context->playedSeconds > $context->gameDurationSeconds * 0.3
            && $context->playedSeconds < $context->gameDurationSeconds * 0.6
            && $context->impPerStart > $context->teamAverageGameImpPerStart * 1.5;
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        $minutes = $context->playedSeconds / 60;
        return $minutes > 0 ? ($context->impPerStart / $minutes) : 0.0;
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
        return 'spark_plug';
    }
}
