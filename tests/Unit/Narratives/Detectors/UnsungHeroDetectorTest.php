<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\UnsungHeroDetector;
use Tests\TestCase;

class UnsungHeroDetectorTest extends TestCase
{
    private UnsungHeroDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new UnsungHeroDetector();
    }

    public function test_it_detects_unsung_hero()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 10,
            'maxGamePoints' => 30, // < 50%
            'impPerStart' => 12.0,
            'maxGameImp' => 15.0, // > 80%
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_team_lost()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'points' => 10,
            'maxGamePoints' => 30,
            'impPerStart' => 12.0,
            'maxGameImp' => 15.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_points_high()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 20, // > 50% of 30
            'maxGamePoints' => 30,
            'impPerStart' => 12.0,
            'maxGameImp' => 15.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_imp_low()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 10,
            'maxGamePoints' => 30,
            'impPerStart' => 10.0, // < 80% of 15
            'maxGameImp' => 15.0,
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
            teamWon: $overrides['teamWon'] ?? true,
            teamAverageGameImpPerStart: 0.0,
            maxGameImp: $overrides['maxGameImp'] ?? 15.0,
            maxGamePoints: $overrides['maxGamePoints'] ?? 30,
            maxGameRebounds: 10,
            maxGameAssists: 10,
            gameDurationSeconds: 2400
        );
    }
}
