<?php

declare(strict_types=1);

namespace App\Dtos;

use App\Models\GameTeamPlayerStat;
use App\Models\Player;

readonly final class RankedPlayerDto
{
    public function __construct(private int $leaderboardPosition, private int $playerId, private Player $player, private int $games, private float $avgImp)
    {
    }

    public function getLeaderboardPosition(): int
    {
        return $this->leaderboardPosition;
    }

    public function getPlayerId(): int
    {
        return $this->playerId;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGames(): int
    {
        return $this->games;
    }

    public function getAvgImp(): float
    {
        return $this->avgImp;
    }
}