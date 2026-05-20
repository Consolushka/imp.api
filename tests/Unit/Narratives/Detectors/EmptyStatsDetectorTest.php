<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\EmptyStatsDetector;
use Tests\TestCase;

class EmptyStatsDetectorTest extends TestCase
{
    private EmptyStatsDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new EmptyStatsDetector();
    }

    public function test_it_detects_empty_stats()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'points' => 25,
            'maxGamePoints' => 30, // > 80%
            'impPerStart' => -2.0,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_team_won()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 25,
            'maxGamePoints' => 30,
            'impPerStart' => -2.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_points_low()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'points' => 15, // < 80% of 30
            'maxGamePoints' => 30,
            'impPerStart' => -2.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_imp_positive()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'points' => 25,
            'maxGamePoints' => 30,
            'impPerStart' => 1.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    private function createContext(array $overrides): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            playerId: 1,
            points: $overrides['points'] ?? 0,
            rebounds: 0,
            assists: 0,
            steals: 0,
            blocks: 0,
            playedSeconds: 1800,
            plusMinus: 0,
            impPerStart: $overrides['impPerStart'] ?? 0.0,
            teamWon: $overrides['teamWon'] ?? false,
            teamAverageGameImpPerStart: 0.0,
            maxGameImp: 10.0,
            maxGamePoints: $overrides['maxGamePoints'] ?? 30,
            maxGameRebounds: 10,
            maxGameAssists: 10,
            gameDurationSeconds: 2400
        );
    }
}
