<?php

declare(strict_types=1);

namespace App\Service\Imp\Dtos;

use App\Service\Imp\PersEnum;

final readonly class ImpPerDto
{
    public function __construct(public PersEnum $per, public float $imp)
    {

    }
}