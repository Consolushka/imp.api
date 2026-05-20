<?php

namespace App\Service\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class TripleDoubleDetector implements NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool
    {
        $categories = 0;
        if ($context->points >= 10) $categories++;
        if ($context->rebounds >= 10) $categories++;
        if ($context->assists >= 10) $categories++;
        if ($context->steals >= 10) $categories++;
        if ($context->blocks >= 10) $categories++;

        return $categories >= 3;
    }

    public function getTier(): int
    {
        return 2;
    }

    public function isAdditive(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return 'triple_double';
    }
}
