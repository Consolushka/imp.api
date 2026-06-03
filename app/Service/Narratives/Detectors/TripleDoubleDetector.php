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

    public function getValue(PlayerNarrativeContext $context): string
    {
        $stats = [
            $context->points . ' PTS' => $context->points,
            $context->rebounds . ' REB' => $context->rebounds,
            $context->assists . ' AST' => $context->assists,
            $context->steals . ' STL' => $context->steals,
            $context->blocks . ' BLK' => $context->blocks,
        ];

        arsort($stats);

        return (string) array_key_first($stats);
    }

    public function getWeight(PlayerNarrativeContext $context): float
    {
        return (float) ($context->points + $context->rebounds + $context->assists + $context->steals + $context->blocks);
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
