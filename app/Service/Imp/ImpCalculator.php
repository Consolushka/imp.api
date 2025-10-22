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

        // 9/37 = 0,1914893617
        $playerImpPerMinute = $plsMin / ($playedSeconds / 60);
        // 1/48 = 0,02083333333
        $fullGameImpPerMinute = $finalDiff / $gamePlayedMinutes;

        // 0,22240991
        return $playerImpPerMinute - $fullGameImpPerMinute;
    }

    public static function evaluatePer(int $playedSeconds, int $plsMin, int $finalDiff, int $gamePlayedMinutes, PersEnum $per): float
    {
        $cleanImp = self::evaluateClean($playedSeconds, $plsMin, $finalDiff, $gamePlayedMinutes);

        $timeBase = TimeBasesEnum::fromGameDurationAndPer($gamePlayedMinutes, $per);;

        $reliability = $timeBase->calculateReliability($playedSeconds / 60);;
        $pure = $cleanImp * $timeBase->value * $reliability;;

        return $pure * $reliability;
    }
}