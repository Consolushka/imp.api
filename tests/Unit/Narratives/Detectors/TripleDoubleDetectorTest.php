<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\TripleDoubleDetector;
use Tests\TestCase;

class TripleDoubleDetectorTest extends TestCase
{
    private TripleDoubleDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new TripleDoubleDetector();
    }

    public function test_it_returns_true_for_classic_triple_double()
    {
        $context = $this->createContext([
            'points' => 10,
            'rebounds' => 10,
            'assists' => 10,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_returns_true_for_triple_double_with_steals()
    {
        $context = $this->createContext([
            'points' => 10,
            'rebounds' => 10,
            'assists' => 5,
            'steals' => 10,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_returns_true_for_triple_double_with_blocks()
    {
        $context = $this->createContext([
            'points' => 10,
            'rebounds' => 5,
            'assists' => 5,
            'steals' => 10,
            'blocks' => 10,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_returns_false_for_double_double()
    {
        $context = $this->createContext([
            'points' => 15,
            'rebounds' => 12,
            'assists' => 8,
            'steals' => 2,
            'blocks' => 0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_correct_value()
    {
        $context = $this->createContext([
            'points' => 12,
            'rebounds' => 10,
            'assists' => 11,
        ]);
        $this->assertEquals('12 PTS', $this->detector->getValue($context));
    }

    private function createContext(array $overrides): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            playerId: $overrides['playerId'] ?? 1,
            points: $overrides['points'] ?? 0,
            rebounds: $overrides['rebounds'] ?? 0,
            assists: $overrides['assists'] ?? 0,
            steals: $overrides['steals'] ?? 0,
            blocks: $overrides['blocks'] ?? 0,
            playedSeconds: $overrides['playedSeconds'] ?? 1800,
            plusMinus: $overrides['plusMinus'] ?? 0,
            impPerStart: $overrides['impPerStart'] ?? 0.0,
            fieldGoalsPercentage: 0.0,
            teamWon: $overrides['teamWon'] ?? true,
            teamAverageGameImpPerStart: $overrides['teamAverageGameImpPerStart'] ?? 0.0,
            maxGameImp: $overrides['maxGameImp'] ?? 0.0,
            maxGamePoints: $overrides['maxGamePoints'] ?? 0,
            maxGameRebounds: $overrides['maxGameRebounds'] ?? 0,
            maxGameAssists: $overrides['maxGameAssists'] ?? 0,
            gameDurationSeconds: $overrides['gameDurationSeconds'] ?? 2400
        );
    }
}
