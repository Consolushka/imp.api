<?php

namespace App\Service\Narratives\Contracts;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;

interface DailyInsightDetector
{
    /**
     * @return DailyInsightDto[]
     */
    public function analyze(DailyContext $context): array;
}
