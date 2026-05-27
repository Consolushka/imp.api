<?php

namespace App\Dtos;

readonly class PlayerNarrativeContext
{
    public function __construct(
        public int   $playerId,
        public int   $points,
        public int   $rebounds,
        public int   $assists,
        public int   $steals,
        public int   $blocks,
        public int   $playedSeconds,
        public int   $plusMinus,
        public float $impPerStart,
        public float $fieldGoalsPercentage,
        public bool  $teamWon,
        // Contextual game/tournament data for comparison
        public float $teamAverageGameImpPerStart,
        public float $maxGameImp,
        public int   $maxGamePoints,
        public int   $maxGameRebounds,
        public int   $maxGameAssists,
        public int   $gameDurationSeconds,
    ) {}
}
