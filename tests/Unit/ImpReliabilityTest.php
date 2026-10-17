<?php

namespace Tests\Unit;

use App\Service\Imp\TimeBasesEnum;
use PHPUnit\Framework\TestCase;

class ImpReliabilityTest extends TestCase
{
    /**
     * @dataProvider reliabilityDataProvider
     */
    public function test_reliability_calculation(TimeBasesEnum $base, float $minutes, float $expected): void
    {
        $this->assertEquals($expected, $base->calculateReliability($minutes));
    }

    public static function reliabilityDataProvider(): array
    {
        // Рост до пика (30 мин для 40-минутной базы, 36 для 48-минутной) идёт по smoothstep 3t²-2t³, дальше затухание exp(-0.005*(x-пик)).
        return [
            [TimeBasesEnum::Per40, 0.0, 0.0],
            [TimeBasesEnum::Per40, 5.0, 0.07],
            [TimeBasesEnum::Per40, 15.0, 0.5],
            [TimeBasesEnum::Per40, 30.0, 1.0],
            [TimeBasesEnum::Per48, 9.0, 0.16],
            [TimeBasesEnum::Per48, 18.0, 0.5],
            [TimeBasesEnum::Per48, 36.0, 1.0],
        ];
    }
}
