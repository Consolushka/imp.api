<?php

namespace Tests\Unit\Narratives;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Contracts\NarrativeDetector;
use App\Service\Narratives\NarrativeEngine;
use Mockery;
use Tests\TestCase;

class NarrativeEngineTest extends TestCase
{
    public function test_it_resolves_tiers_correctly()
    {
        // Tier 1 non-additive
        $t1 = $this->mockDetector('t1', 1, false, true);
        // Tier 2 non-additive (should be suppressed)
        $t2 = $this->mockDetector('t2', 2, false, true);
        // Tier 3 additive (should be kept)
        $t3 = $this->mockDetector('t3', 3, true, true);

        $engine = new NarrativeEngine([$t1, $t2, $t3]);
        $context = $this->createContext();

        $results = $engine->analyze($context);

        $this->assertCount(2, $results);
        $this->assertContains('t1', $results);
        $this->assertContains('t3', $results);
        $this->assertNotContains('t2', $results);
    }

    public function test_it_takes_first_non_additive_of_same_tier()
    {
        // First Tier 1 non-additive
        $t1a = $this->mockDetector('t1a', 1, false, true);
        // Second Tier 1 non-additive (should be suppressed because t1a came first)
        $t1b = $this->mockDetector('t1b', 1, false, true);

        $engine = new NarrativeEngine([$t1a, $t1b]);
        $context = $this->createContext();

        $results = $engine->analyze($context);

        $this->assertCount(1, $results);
        $this->assertContains('t1a', $results);
        $this->assertNotContains('t1b', $results);
    }

    public function test_it_keeps_multiple_additive_narratives()
    {
        $t1 = $this->mockDetector('t1', 2, true, true);
        $t2 = $this->mockDetector('t2', 3, true, true);

        $engine = new NarrativeEngine([$t1, $t2]);
        $context = $this->createContext();

        $results = $engine->analyze($context);

        $this->assertCount(2, $results);
        $this->assertContains('t1', $results);
        $this->assertContains('t2', $results);
    }

    public function test_it_returns_empty_when_no_detectors_match()
    {
        $t1 = $this->mockDetector('t1', 1, false, false);

        $engine = new NarrativeEngine([$t1]);
        $context = $this->createContext();

        $results = $engine->analyze($context);

        $this->assertEmpty($results);
    }

    private function mockDetector(string $slug, int $tier, bool $additive, bool $detected): NarrativeDetector
    {
        $mock = Mockery::mock(NarrativeDetector::class);
        $mock->shouldReceive('getSlug')->andReturn($slug);
        $mock->shouldReceive('getTier')->andReturn($tier);
        $mock->shouldReceive('isAdditive')->andReturn($additive);
        $mock->shouldReceive('isDetected')->andReturn($detected);
        
        return $mock;
    }

    private function createContext(): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            1, 0, 0, 0, 0, 0, 0, 0, 0.0, true, 0.0, 0.0, 0, 0, 0, 2400
        );
    }
}
