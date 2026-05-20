<?php

namespace App\Service\Narratives;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;

class NarrativeEngine
{
    /** @var NarrativeDetector[] */
    private array $detectors = [];

    public function __construct(iterable $detectors)
    {
        foreach ($detectors as $detector) {
            $this->addDetector($detector);
        }
    }

    public function addDetector(NarrativeDetector $detector): void
    {
        $this->detectors[] = $detector;
    }

    /**
     * @return string[] Slugs of detected narratives
     */
    public function analyze(PlayerNarrativeContext $context): array
    {
        $additiveSlugs = [];
        $topNonAdditiveSlug = null;
        $minTier = 999;

        foreach ($this->detectors as $detector) {
            if ($detector->isDetected($context)) {
                if ($detector->isAdditive()) {
                    $additiveSlugs[] = $detector->getSlug();
                } else {
                    // Update only if this detector is of a higher tier (lower number)
                    // than what we've found so far. This ensures the first one of the 
                    // highest tier is kept.
                    if ($detector->getTier() < $minTier) {
                        $minTier = $detector->getTier();
                        $topNonAdditiveSlug = $detector->getSlug();
                    }
                }
            }
        }

        $finalSlugs = $additiveSlugs;
        if ($topNonAdditiveSlug !== null) {
            $finalSlugs[] = $topNonAdditiveSlug;
        }

        return array_values(array_unique($finalSlugs));
    }
}
