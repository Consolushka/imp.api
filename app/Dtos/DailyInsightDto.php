<?php

declare(strict_types=1);

namespace App\Dtos;

readonly final class DailyInsightDto
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?int $playerId = null,
        public ?string $playerFullName = null,
        public ?int $gameId = null,
        public ?string $value = null,
        public ?string $text = null,
        public array $placeholders = [],
    ) {}

    public function withText(?string $text): self
    {
        return new self(
            slug: $this->slug,
            title: $this->title,
            playerId: $this->playerId,
            playerFullName: $this->playerFullName,
            gameId: $this->gameId,
            value: $this->value,
            text: $text,
            placeholders: $this->placeholders
        );
    }
}
