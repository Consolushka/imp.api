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
        return [
            // FIBA (k=15)
            // x=15, k=15 => 225 / (225 + 225) = 0.5
            [TimeBasesEnum::Per40, 15.0, 0.5],
            // x=0 => 0
            [TimeBasesEnum::Per40, 0.0, 0.0],
            // x=5 => 25 / (25 + 225) = 25 / 250 = 0.1
            [TimeBasesEnum::Per40, 5.0, 0.1],
            
            // NBA (k=18)
            // x=18, k=18 => 0.5
            [TimeBasesEnum::Per48, 18.0, 0.5],
            // x=9 => 81 / (81 + 324) = 81 / 405 = 0.2
            [TimeBasesEnum::Per48, 9.0, 0.2],
        ];
    }
}
