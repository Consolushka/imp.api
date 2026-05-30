<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\CardioSessionDetector;
use Tests\TestCase;

class CardioSessionDetectorTest extends TestCase
{
    private CardioSessionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CardioSessionDetector();
    }

    public function test_it_detects_cardio_session()
    {
        $context = $this->createContext([
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400, // 75%
            'points' => 1, // < 10% of 30
            'rebounds' => 1, // < 10% of 15
            'assists' => 1, // < 10% of 15
            'impPerStart' => 0.5, // abs < 1.0
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_low_minutes()
    {
        $context = $this->createContext([
            'playedSeconds' => 1000, // < 75%
            'gameDurationSeconds' => 2400,
            'points' => 1,
            'rebounds' => 1,
            'assists' => 1,
            'impPerStart' => 0.5,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_points_too_high()
    {
        $context = $this->createContext([
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400,
            'points' => 10, // > 10% of 30
            'rebounds' => 1,
            'assists' => 1,
            'impPerStart' => 0.5,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_imp_impactful()
    {
        $context = $this->createContext([
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400,
            'points' => 1,
            'rebounds' => 1,
            'assists' => 1,
            'impPerStart' => 2.5, // abs > 1.0
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_correct_value()
    {
        $context = $this->createContext(['impPerStart' => 0.5]);
        $this->assertEquals('0.5 IMP', $this->detector->getValue($context));
    }

    private function createContext(array $overrides): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            playerId: 1,
            points: $overrides['points'] ?? 0,
            rebounds: $overrides['rebounds'] ?? 0,
            assists: $overrides['assists'] ?? 0,
            steals: 0,
            blocks: 0,
            playedSeconds: $overrides['playedSeconds'] ?? 0,
            plusMinus: 0,
            impPerStart: $overrides['impPerStart'] ?? 0.0,
            fieldGoalsPercentage: 0.0,
            teamWon: true,
            teamAverageGameImpPerStart: 0.0,
            maxGameImp: 15.0,
            maxGamePoints: 30,
            maxGameRebounds: 15,
            maxGameAssists: 15,
            gameDurationSeconds: $overrides['gameDurationSeconds'] ?? 2400
        );
    }
}
