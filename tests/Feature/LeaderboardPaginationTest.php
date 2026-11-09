<?php

namespace Tests\Feature;

use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class LeaderboardPaginationTest extends TestCase
{
    private const URI = '/api/leaderboard?tournament_id=1&per=fullGame';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tournaments', function ($table) {
            $table->id();
        });

        Schema::create('players', function ($table) {
            $table->id();
        });
        Schema::create('teams', function ($table) {
            $table->id();
            $table->string('alias');
        });

        DB::table('tournaments')->insert(['id' => 1]);
        DB::table('players')->insert(array_map(fn (int $id) => ['id' => $id], range(1, 25)));
        DB::table('teams')->insert(['id' => 1, 'alias' => 'TEAM']);
    }

    private function mockRanking(int $calculations = 1): void
    {
        $rows = array_map(fn (int $playerId) => [$playerId, 1, 1200, 10.0], range(1, 25));

        $this->partialMock(RankingService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('calculateBase')
            ->times($calculations)
            ->andReturn($rows));
    }

    private function positions(string $query): array
    {
        return array_column($this->getJson(self::URI.$query)->assertOk()->json('data'), 'position');
    }

    public function test_second_page_continues_positions(): void
    {
        $this->mockRanking();

        $response = $this->getJson(self::URI.'&limit=10&page=2')->assertOk();

        $this->assertSame(range(11, 20), array_column($response->json('data'), 'position'));
        $this->assertSame(25, $response->json('meta.total'));
    }

    public function test_page_defaults_to_first(): void
    {
        $this->mockRanking();

        $this->assertSame(range(1, 10), $this->positions('&limit=10'));
    }

    public function test_page_past_the_end_is_empty_and_keeps_total(): void
    {
        $this->mockRanking();

        foreach ([4, PHP_INT_MAX] as $page) {
            $this->getJson(self::URI."&limit=10&page={$page}")
                ->assertOk()
                ->assertExactJson(['data' => [], 'meta' => ['total' => 25]]);
        }
    }

    public function test_page_below_one_is_rejected(): void
    {
        $this->getJson(self::URI.'&page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_pages_and_limits_share_one_calculation(): void
    {
        $this->mockRanking();

        $this->assertSame(range(1, 10), $this->positions('&limit=10&page=1'));
        $this->assertSame(range(11, 20), $this->positions('&limit=10&page=2'));
        $this->assertSame(range(6, 10), $this->positions('&limit=5&page=2'));
    }

    public function test_omitted_params_and_their_defaults_share_a_calculation(): void
    {
        $this->mockRanking();

        $this->positions('');
        $this->positions('&order=desc&min_games=1&use_reliability=1');
    }

    public function test_cheap_filters_share_one_calculation(): void
    {
        $this->mockRanking();

        $this->positions('');

        foreach (['&order=asc', '&min_games=2', '&team_id=1', '&min_minutes=1', '&avg_minutes=1'] as $filter) {
            $this->positions($filter);
        }
    }

    public function test_total_and_positions_follow_the_filtered_ranking(): void
    {
        $this->partialMock(RankingService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('calculateBase')
            ->once()
            ->andReturn([[1, 1, 1200, 10.0], [1, 1, 1200, 20.0], [2, 1, 1200, 30.0]]));

        $response = $this->getJson(self::URI.'&min_games=2')->assertOk();

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame([1], array_column($response->json('data'), 'position'));
        $this->assertSame(1, $response->json('data.0.player.id'));
    }

    public function test_tournament_per_and_reliability_each_get_their_own_calculation(): void
    {
        DB::table('tournaments')->insert(['id' => 2]);

        $this->mockRanking(calculations: 4);

        foreach (['', '&tournament_id=2', '&per=clean', '&use_reliability=0'] as $filter) {
            $this->positions($filter);
        }
    }

    public function test_last_page_can_be_partial(): void
    {
        $this->mockRanking();

        $this->assertSame(range(21, 25), $this->positions('&limit=10&page=3'));
    }

    public function test_page_without_limit_uses_default_limit(): void
    {
        $this->mockRanking();

        $this->assertSame(range(11, 20), $this->positions('&page=2'));
    }
}
