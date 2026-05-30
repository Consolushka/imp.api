<?php

namespace Tests\Unit\Narratives\Detectors;

use App\Dtos\PlayerNarrativeContext;
use App\Service\Narratives\Detectors\DifferenceMakerDetector;
use Tests\TestCase;

class DifferenceMakerDetectorTest extends TestCase
{
    private DifferenceMakerDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DifferenceMakerDetector();
    }

    public function test_it_returns_true_when_player_was_a_difference_maker_by_points()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'impPerStart' => 10.0,
            'teamAverageGameImpPerStart' => 5.0,
            'points' => 25,
            'maxGamePoints' => 30, // 80% is 24
            'maxGameImp' => 15.0,
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_returns_true_when_player_was_a_difference_maker_by_imp()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'impPerStart' => 13.0,
            'teamAverageGameImpPerStart' => 5.0,
            'points' => 15,
            'maxGamePoints' => 30,
            'maxGameImp' => 15.0, // 80% is 12
        ]);

        $this->assertTrue($this->detector->isDetected($context));
    }

    public function test_it_returns_false_when_team_lost()
    {
        $context = $this->createContext([
            'teamWon' => false,
            'impPerStart' => 20.0,
            'teamAverageGameImpPerStart' => 5.0,
            'points' => 30,
            'maxGamePoints' => 30,
            'maxGameImp' => 20.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_false_when_imp_is_below_average()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'impPerStart' => 4.0,
            'teamAverageGameImpPerStart' => 5.0,
            'points' => 30,
            'maxGamePoints' => 30,
            'maxGameImp' => 10.0,
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_false_when_neither_points_nor_imp_are_high_enough()
    {
        $context = $this->createContext([
            'teamWon' => true,
            'impPerStart' => 6.0,
            'teamAverageGameImpPerStart' => 5.0,
            'points' => 20,
            'maxGamePoints' => 30, // 80% is 24
            'maxGameImp' => 15.0, // 80% is 12
        ]);

        $this->assertFalse($this->detector->isDetected($context));
    }

    public function test_it_returns_correct_value()
    {
        $context = $this->createContext(['impPerStart' => 10.0]);
        $this->assertEquals('10.0 IMP', $this->detector->getValue($context));
    }

    private function createContext(array $overrides): PlayerNarrativeContext
    {
        return new PlayerNarrativeContext(
            playerId: $overrides['playerId'] ?? 1,
            points: $overrides['points'] ?? 10,
            rebounds: $overrides['rebounds'] ?? 5,
            assists: $overrides['assists'] ?? 5,
            steals: $overrides['steals'] ?? 1,
            blocks: $overrides['blocks'] ?? 1,
            playedSeconds: $overrides['playedSeconds'] ?? 1800,
            plusMinus: $overrides['plusMinus'] ?? 5,
            impPerStart: $overrides['impPerStart'] ?? 5.0,
            fieldGoalsPercentage: 0.0,
            teamWon: $overrides['teamWon'] ?? true,
            teamAverageGameImpPerStart: $overrides['teamAverageGameImpPerStart'] ?? 4.0,
            maxGameImp: $overrides['maxGameImp'] ?? 10.0,
            maxGamePoints: $overrides['maxGamePoints'] ?? 20,
            maxGameRebounds: $overrides['maxGameRebounds'] ?? 10,
            maxGameAssists: $overrides['maxGameAssists'] ?? 10,
            gameDurationSeconds: $overrides['gameDurationSeconds'] ?? 2400
        );
    }
}
