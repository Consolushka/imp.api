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
        if ($gameDuration === 40) {
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

        if ($gameDuration === 48) {
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

        throw new \Exception('To be implemented');
    }

    public function calculateReliability(float $minutesPlayed): float
    {
        $lowerEdgeCoefficient = 0.3947;

        $lowerEdge = $lowerEdgeCoefficient * $this->value;

        if ($minutesPlayed < $lowerEdge) {
            return $this->insufficientDistanceCoefficient() * pow($minutesPlayed, $this->insufficientDistancePower());
        }

        if ($minutesPlayed < $this->value) {
            return $this->sufficientDistanceOffset() + (pow($minutesPlayed - $lowerEdge, $this->sufficientDistancePower())) / (pow($this->value - $lowerEdge, $this->sufficientDistancePower())) * (1 - $this->sufficientDistanceOffset());
        }

        return 1 - (pow($minutesPlayed - $this->value, $this->overSufficientDistanceUpperPower())) / (pow($this->value - $lowerEdge, $this->overSufficientDistanceLowerPower()));
    }

    private function insufficientDistanceCoefficient(): float
    {
        return match ($this) {
            self::Per20 => 0.05,
            self::Per24 => 0.0003,
            self::Per30 => 0.00024,
            self::Per36 => 0.00012,
            self::Per40 => 0.00008,
            self::Per48 => 0.000034,
        };
    }

    private function insufficientDistancePower(): float
    {
        return match ($this) {
            self::Per20 => 1.0,
            self::Per24 => 2.8,
            self::Per30, self::Per36, self::Per40 => 3.0,
            self::Per48 => 3.2,
        };
    }

    private function sufficientDistanceOffset(): float
    {
        return match ($this) {
            self::Per20 => 0.4,
            self::Per24 => 0.189,
            self::Per30 => 0.252,
            self::Per36 => 0.405,
            self::Per40 => 0.327,
            self::Per48 => 0.42,
        };
    }

    private function sufficientDistancePower(): float
    {
        return match ($this) {
            self::Per20, self::Per24, self::Per30, self::Per36 => 0.6,
            self::Per40 => 0.8,
            self::Per48 => 0.4,
        };
    }

    private function overSufficientDistanceUpperPower(): float
    {
        return match ($this) {
            self::Per20, self::Per24, self::Per30, self::Per36, self::Per40, self::Per48 => 2.0,
        };
    }

    private function overSufficientDistanceLowerPower(): float
    {
        return match ($this) {
            self::Per20 => 3.1,
            self::Per24 => 2.8,
            self::Per30 => 2.3,
            self::Per36 => 2.2,
            self::Per40 => 2.0,
            self::Per48 => 1.9,
        };
    }
}