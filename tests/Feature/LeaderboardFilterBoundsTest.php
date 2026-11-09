<?php

namespace Tests\Feature;

use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaderboardFilterBoundsTest extends TestCase
{
    private const URI = '/api/leaderboard?tournament_id=1&per=fullGame';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tournaments', function ($table) {
            $table->id();
        });

        DB::table('tournaments')->insert(['id' => 1]);
    }

    public static function thresholdsAboveMaximum(): array
    {
        return [
            'min_games' => ['min_games', 101],
            'min_minutes' => ['min_minutes', 49],
            'avg_minutes' => ['avg_minutes', 49],
        ];
    }

    #[DataProvider('thresholdsAboveMaximum')]
    public function test_threshold_above_maximum_is_rejected_without_calculation(string $field, int $value): void
    {
        $this->mock(RankingService::class)->shouldNotReceive('calculateBase');

        $this->getJson(self::URI."&{$field}={$value}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function thresholdsAtMaximum(): array
    {
        return [
            'min_games' => ['min_games', 100],
            'min_minutes' => ['min_minutes', 48],
            'avg_minutes' => ['avg_minutes', 48],
        ];
    }

    #[DataProvider('thresholdsAtMaximum')]
    public function test_threshold_at_maximum_is_accepted(string $field, int $value): void
    {
        $this->partialMock(RankingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculateBase')->once()->andReturn([]);
            $mock->shouldReceive('hydrate')->andReturn([]);
        });

        $this->getJson(self::URI."&{$field}={$value}")->assertOk();
    }
}
