<?php

declare(strict_types=1);

namespace App\Dtos;

final class GameTeamPlayerStatsDto
{
    public function __construct(
        public int  $id,
        public ?int $plus_minus,
        public ?int $played_seconds,
        public ?int $final_differential,
        public ?int $duration,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            plus_minus: $data['plus_minus'] ?? null,
            played_seconds: $data['played_seconds'] ?? null,
            final_differential: $data['final_differential'] ?? null,
            duration: $data['duration'] ?? null,
        );
    }
}