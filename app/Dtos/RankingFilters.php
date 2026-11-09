<?php

declare(strict_types=1);

namespace App\Dtos;

final readonly class RankingFilters
{
    public function __construct(
        public int $tournamentId,
        public string $per,
        public string $order,
        public int $minGames,
        public ?int $teamId,
        public ?int $minMinutes,
        public ?int $avgMinutes,
        public bool $useReliability,
    ) {}
}
