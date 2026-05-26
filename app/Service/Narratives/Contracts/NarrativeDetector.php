<?php

namespace App\Service\Narratives\Contracts;

use App\Dtos\PlayerNarrativeContext;

interface NarrativeDetector
{
    public function isDetected(PlayerNarrativeContext $context): bool;

    public function getWeight(PlayerNarrativeContext $context): float;

    public function getTier(): int;

    public function isAdditive(): bool;

    public function getSlug(): string;
}
