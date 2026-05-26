<?php

namespace App\Service\Narratives;

use App\Dtos\DailyContext;
use App\Dtos\DailyInsightDto;
use App\Service\Narratives\Contracts\DailyInsightDetector;

class DailyInsightEngine
{
    /** @var DailyInsightDetector[] */
    private array $detectors = [];

    public function __construct(
        iterable $detectors,
        private readonly NarrativeTemplateService $templateService
    ) {
        foreach ($detectors as $detector) {
            $this->addDetector($detector);
        }
    }

    public function addDetector(DailyInsightDetector $detector): void
    {
        $this->detectors[] = $detector;
    }

    /**
     * @return DailyInsightDto[]
     */
    public function analyze(DailyContext $context): array
    {
        $insights = [];
        foreach ($this->detectors as $detector) {
            $detected = $detector->analyze($context);
            foreach ($detected as $dto) {
                $text = $this->templateService->enrich($dto->slug, $dto->placeholders);
                $insights[] = $dto->withText($text);
            }
        }

        return $insights;
    }
}
