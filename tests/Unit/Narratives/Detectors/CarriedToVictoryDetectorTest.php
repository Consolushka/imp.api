<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\CarriedToVictoryDetector;
use Tests\TestCase;

class CarriedToVictoryDetectorTest extends TestCase
{
    private CarriedToVictoryDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CarriedToVictoryDetector();
    }

    public function test_it_detects_carried_to_victory()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400, // 75% played
            'impPerStart' => -5.0,
            'teamAverageGameImpPerStart' => 2.0,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_team_lost()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400,
            'impPerStart' => -5.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_low_minutes()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'playedSeconds' => 1000, // < 70%
            'gameDurationSeconds' => 2400,
            'impPerStart' => -5.0,
            'teamAverageGameImpPerStart' => 2.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_does_not_detect_if_imp_is_positive()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'playedSeconds' => 1800,
            'gameDurationSeconds' => 2400,
            'impPerStart' => 1.0,
            'teamAverageGameImpPerStart' => 0.5,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_correct_value()
    {
        $context = $this->createContext(['impPerStart' => -5.0]);
        $this->assertEquals('-5.0 IMP', $this->detector->getValue($context));
    }

    private function createContext(array $overrides): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            playerId: 1,
            points: $overrides['points'] ?? 5,
            rebounds: $overrides['rebounds'] ?? 5,
            assists: $overrides['assists'] ?? 5,
            steals: 1,
            blocks: 1,
            playedSeconds: $overrides['playedSeconds'] ?? 1800,
            plusMinus: $overrides['plusMinus'] ?? 5,
            impPerStart: $overrides['impPerStart'] ?? 5.0,
            fieldGoalsPercentage: 0.0,
            teamWon: $overrides['teamWon'] ?? true,
            teamAverageGameImpPerStart: $overrides['teamAverageGameImpPerStart'] ?? 4.0,
            maxGameImp: 10.0,
            maxGamePoints: 20,
            maxGameRebounds: 10,
            maxGameAssists: 10,
            gameDurationSeconds: $overrides['gameDurationSeconds'] ?? 2400
        );
    }
}
