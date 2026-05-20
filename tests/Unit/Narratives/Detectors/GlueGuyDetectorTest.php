<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\GlueGuyDetector;
use Tests\TestCase;

class GlueGuyDetectorTest extends TestCase
{
    private GlueGuyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new GlueGuyDetector();
    }

    public function test_it_detects_glue_guy()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 5,
            'maxGamePoints' => 30, // < 45% (13.5)
            'impPerStart' => 10.0,
            'maxGameImp' => 15.0, // > 60% (9.0)
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_points_too_high()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 15, // > 45% of 30
            'maxGamePoints' => 30,
            'impPerStart' => 10.0,
            'maxGameImp' => 15.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_imp_too_low()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'points' => 5,
            'maxGamePoints' => 30,
            'impPerStart' => 8.0, // < 60% of 15
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
