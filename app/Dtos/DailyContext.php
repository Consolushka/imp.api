<?php

declare(strict_types=1);

namespace App\Dtos;

use Illuminate\Support\Collection;

readonly final class DailyContext
{
    /**
     * @param Collection<\App\Models\Game> $games
     * @param Collection<\App\Models\GameTeamPlayerStat> $playerStats (Enriched with imp)
     */
    public function __construct(
        public Collection $games,
        public Collection $playerStats
    ) {}
}
