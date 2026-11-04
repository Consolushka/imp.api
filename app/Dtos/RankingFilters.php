<?php

declare(strict_types=1);

namespace App\Dtos;

/**
 * Всё, от чего зависит рейтинг. Сервис видит только эти значения, и по ним же рейтинг ищут в кеше,
 * поэтому поля должны оставаться публичными: закрытые не попадут в ключ кеша.
 */
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
