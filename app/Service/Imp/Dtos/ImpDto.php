<?php

declare(strict_types=1);

namespace App\Service\Imp\Dtos;

final readonly class ImpDto
{
    public function __construct(public int $gameTeamPlayerStatId, public array $perDto)
    {

    }
}