<?php

namespace Tests\Feature;

use App\Dtos\RankedPlayerDto;
use App\Models\Player;
use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        DB::table('tournaments')->insert(['id' => 1]);
    }

    private function mockRanking(int $calculations = 1): void
    {
        $leaderboard = array_map(
            fn (int $position) => new RankedPlayerDto($position, $position, new Player, 'TEAM', 5, 10.0, 1200),
            range(1, 25)
        );

        $this->mock(RankingService::class)
            ->shouldReceive('calculate')
            ->times($calculations)
            ->andReturn($leaderboard);
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

    public function test_each_filter_gets_its_own_calculation(): void
    {
        Schema::create('teams', function ($table) {
            $table->id();
        });

        DB::table('teams')->insert(['id' => 1]);
        DB::table('tournaments')->insert(['id' => 2]);

        $filters = [
            '&tournament_id=2',
            '&per=clean',
            '&order=asc',
            '&min_games=2',
            '&team_id=1',
            '&min_minutes=1',
            '&avg_minutes=1',
            '&use_reliability=0',
        ];

        $this->mockRanking(calculations: count($filters) + 1);

        $this->positions('');

        foreach ($filters as $filter) {
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
