<?php

declare(strict_types=1);

namespace App\Service\Imp;

enum TimeBasesEnum: int
{
    case Per20 = 20;
    case Per24 = 24;
    case Per30 = 30;
    case Per36 = 36;
    case Per40 = 40;
    case Per48 = 48;

    public static function fromGameDurationAndPer(int $gameDuration, PersEnum $persEnum): self
    {
        $is40Base = $gameDuration >= 40 && ($gameDuration - 40) % 5 === 0;
        $is48Base = $gameDuration >= 48 && ($gameDuration - 48) % 5 === 0;

        if ($is40Base) {
            switch ($persEnum) {
                case PersEnum::Clean:
                    throw new \Exception('To be implemented');
                case PersEnum::Bench:
                    return self::Per20;
                case PersEnum::Start:
                    return self::Per30;
                case PersEnum::FullGame:
                    return self::Per40;
            }
        }

        if ($is48Base) {
            switch ($persEnum) {
                case PersEnum::Clean:
                    throw new \Exception('To be implemented');
                case PersEnum::Bench:
                    return self::Per24;
                case PersEnum::Start:
                    return self::Per36;
                case PersEnum::FullGame:
                    return self::Per48;
            }
        }

        throw new \InvalidArgumentException("Game duration {$gameDuration} is not supported for IMP calculation. Only 40 and 48 minutes bases (including overtimes) are currently handled.");
    }

    public function calculateReliability(float $minutesPlayed, int $precision = 2): float
    {
        if ($minutesPlayed <= 0) {
            return 0.0;
        }

        $k = $this->isNBA() ? 18 : 15;

        $xSquared = pow($minutesPlayed, 2);
        $kSquared = pow($k, 2);

        $reliability = $xSquared / ($xSquared + $kSquared);

        return round($reliability, $precision);
    }

    private function isNBA(): bool
    {
        return match ($this) {
            self::Per24, self::Per36, self::Per48 => true,
            self::Per20, self::Per30, self::Per40 => false,
        };
    }
}
