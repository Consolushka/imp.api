<?php

declare(strict_types=1);

namespace App\Dtos;

readonly final class PlayerOfTheDayDto
{
    public function __construct(
        public int $id,
        public string $fullName,
        public string $teamAlias,
        public int $playedSeconds,
        public int $pts,
        public int $reb,
        public int $ast,
        public float $imp
    ) {
    }
}
