<?php

declare(strict_types=1);

namespace App\Service\Imp;

final class ImpCalculator
{
    public static function evaluateClean(int $playedSeconds, int $plsMin, int $finalDiff, int $gamePlayedMinutes): float
    {
        if ($playedSeconds === 0) {
            return 0;
        }

        $playerImpPerMinute = $plsMin / ($playedSeconds / 60);
        $fullGameImpPerMinute = $finalDiff / $gamePlayedMinutes;

        return $playerImpPerMinute - $fullGameImpPerMinute;
    }

    public static function evaluatePer(int $playedSeconds, int $plsMin, int $finalDiff, int $gamePlayedMinutes, PersEnum $per, bool $useReliability = true): float
    {
        $cleanImp = self::evaluateClean($playedSeconds, $plsMin, $finalDiff, $gamePlayedMinutes);

        $timeBase = TimeBasesEnum::fromGameDurationAndPer($gamePlayedMinutes, $per);

        $value = $cleanImp * $timeBase->value;

        if (!$useReliability) {
            return $value;
        }

        $reliability = $timeBase->calculateReliability($playedSeconds / 60);

        return $value * $reliability * $reliability;
    }
}