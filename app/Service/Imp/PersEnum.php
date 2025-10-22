<?php

namespace App\Service\Imp;

enum PersEnum: string
{
    case Clean = 'clean';
    case Bench = 'bench';
    case Start = 'start';
    case FullGame = 'fullGame';

    public static function stringCases(): array
    {
        return array_column(self::cases(), 'value');
    }
}
