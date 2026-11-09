<?php

namespace Tests\Feature;

use App\Jobs\WarmTournamentCacheJob;
use App\Models\Tournament;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Tournament\RankingService;
use App\Service\Tournament\TournamentCacheKeys;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WeeklyLeadersService;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TournamentDailyBlocksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-18 12:00:00');

        Schema::create('tournaments', function ($table) {
            $table->id();
            $table->integer('league_id');
            $table->string('name');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->integer('regulation_duration')->nullable();
            $table->smallInteger('tier')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('games', function ($table) {
            $table->id();
            $table->integer('tournament_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('title')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
        Schema::create('game_team_stats', function ($table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('team_id');
            $table->integer('score');
            $table->integer('final_differential')->nullable();
            $table->timestamps();
        });
        Schema::create('teams', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('home_town')->nullable();
            $table->string('alias')->nullable();
            $table->timestamps();
        });
        Schema::create('players', function ($table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->timestamp('birth_date_at')->nullable();
            $table->timestamps();
        });
        Schema::create('game_team_player_stats', function ($table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('team_id');
            $table->integer('player_id');
            $table->integer('played_seconds');
            $table->integer('plus_minus');
            $table->integer('points')->default(0);
            $table->integer('rebounds')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('steals')->default(0);
            $table->integer('blocks')->default(0);
            $table->integer('turnovers')->default(0);
            $table->timestamps();
        });
        // Нужна, потому что обогащение инсайтов текстами ходит в неё на каждый инсайт.
        Schema::create('narrative_templates', function ($table) {
            $table->id();
            $table->string('slug');
            $table->text('body');
            $table->timestamps();
        });

        DB::table('tournaments')->insert([
            ['id' => 1, 'league_id' => 1, 'name' => 'With games', 'regulation_duration' => 40],
            ['id' => 2, 'league_id' => 1, 'name' => 'No games', 'regulation_duration' => 40],
        ]);
        DB::table('tournaments')->insert(
            ['id' => 3, 'league_id' => 1, 'name' => 'Game after end date', 'start_at' => '2026-09-01 00:00:00', 'end_at' => '2026-09-30 00:00:00', 'regulation_duration' => 40],
        );
        DB::table('teams')->insert([
            ['id' => 1, 'alias' => 'HOME'],
            ['id' => 2, 'alias' => 'AWAY'],
        ]);
        DB::table('players')->insert([
            ['id' => 1, 'full_name' => 'Yesterday Hero'],
            ['id' => 2, 'full_name' => 'Old Timer'],
        ]);
        DB::table('games')->insert([
            ['id' => 1, 'tournament_id' => 1, 'scheduled_at' => '2026-10-17 18:00:00', 'duration' => 40],
            ['id' => 2, 'tournament_id' => 1, 'scheduled_at' => '2026-10-10 18:00:00', 'duration' => 40],
            ['id' => 3, 'tournament_id' => 1, 'scheduled_at' => '2026-10-19 18:00:00', 'duration' => 40],
            ['id' => 4, 'tournament_id' => 3, 'scheduled_at' => '2026-10-05 18:00:00', 'duration' => 40],
        ]);
        foreach ([1, 2] as $gameId) {
            DB::table('game_team_stats')->insert([
                ['game_id' => $gameId, 'team_id' => 1, 'score' => 80, 'final_differential' => 10],
                ['game_id' => $gameId, 'team_id' => 2, 'score' => 70, 'final_differential' => -10],
            ]);
        }
        DB::table('game_team_player_stats')->insert([
            ['game_id' => 1, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 2400, 'plus_minus' => 10, 'points' => 25, 'rebounds' => 5, 'assists' => 7],
            ['game_id' => 2, 'team_id' => 1, 'player_id' => 2, 'played_seconds' => 2400, 'plus_minus' => 5, 'points' => 10, 'rebounds' => 0, 'assists' => 0],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_players_of_the_day_default_to_last_game_day(): void
    {
        $response = $this->getJson('/api/tournaments/1/players-of-the-day')->assertOk();

        $this->assertSame('2026-10-17', $response->json('date'));
        $this->assertSame(1, $response->json('data.0.id'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_players_of_the_day_keep_data_item_shape(): void
    {
        $response = $this->getJson('/api/tournaments/1/players-of-the-day')
            ->assertOk()
            ->assertJsonStructure(['date', 'data' => [['id', 'full_name', 'team_alias', 'played_minutes', 'pts', 'reb', 'ast', 'imp']]]);

        $this->assertEqualsCanonicalizing(['date', 'data'], array_keys($response->json()));
    }

    public function test_players_of_the_day_respect_explicit_date(): void
    {
        $response = $this->getJson('/api/tournaments/1/players-of-the-day?date=2026-10-10')->assertOk();

        $this->assertSame('2026-10-10', $response->json('date'));
        $this->assertSame(2, $response->json('data.0.id'));
    }

    public function test_players_of_the_day_without_games_fall_back_to_today(): void
    {
        $response = $this->getJson('/api/tournaments/2/players-of-the-day')->assertOk();

        $this->assertSame('2026-10-18', $response->json('date'));
        $this->assertSame([], $response->json('data'));
    }

    public static function invalidPlayersOfTheDayQueries(): array
    {
        return [
            'empty reliability' => ['use_reliability=', 'use_reliability'],
            'text reliability' => ['use_reliability=abc', 'use_reliability'],
            'text date' => ['date=abc', 'date'],
            'array date' => ['date[]=2026-10-10', 'date'],
            'empty date' => ['date=', 'date'],
            'limit above maximum' => ['limit=101', 'limit'],
            'limit below minimum' => ['limit=0', 'limit'],
            'text limit' => ['limit=abc', 'limit'],
        ];
    }

    #[DataProvider('invalidPlayersOfTheDayQueries')]
    public function test_players_of_the_day_reject_invalid_params(string $query, string $field): void
    {
        $this->getJson("/api/tournaments/1/players-of-the-day?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public function test_players_of_the_day_defaults_read_the_warmed_record(): void
    {
        Cache::put(TournamentCacheKeys::playersOfTheDay(1, 5, true, '2026-10-17'), collect(), now()->addDay());

        $this->getJson('/api/tournaments/1/players-of-the-day')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public static function invalidDates(): array
    {
        return [
            'insights text' => ['daily-insights', 'date=abc'],
            'insights array' => ['daily-insights', 'date[]=2026-10-10'],
            'insights empty' => ['daily-insights', 'date='],
            'leaders text' => ['weekly-leaders', 'date=abc'],
            'leaders array' => ['weekly-leaders', 'date[]=2026-10-10'],
            'leaders empty' => ['weekly-leaders', 'date='],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_date_is_rejected(string $block, string $query): void
    {
        $this->getJson("/api/tournaments/1/{$block}?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public static function blocks(): array
    {
        return [
            'players of the day' => ['players-of-the-day'],
            'daily insights' => ['daily-insights'],
            'weekly leaders' => ['weekly-leaders'],
        ];
    }

    #[DataProvider('blocks')]
    public function test_unknown_tournament_is_not_found(string $block): void
    {
        $this->getJson("/api/tournaments/999/{$block}")->assertNotFound();
        $this->getJson("/api/tournaments/999/{$block}?date=2026-10-17")->assertNotFound();
    }

    #[DataProvider('blocks')]
    public function test_date_outside_the_scheduled_games_is_rejected_and_not_cached(string $block): void
    {
        // У турнира 1 дат нет, сезон — от первой до последней игры в расписании: 2026-10-10..2026-10-19.
        foreach (['2026-10-09', '2026-10-20'] as $date) {
            $this->getJson("/api/tournaments/1/{$block}?date={$date}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('date');
        }

        $this->getJson("/api/tournaments/1/{$block}?date=2026-10-19")->assertOk();

        $this->assertFalse(Cache::has(match ($block) {
            'players-of-the-day' => TournamentCacheKeys::playersOfTheDay(1, 5, true, '2026-10-09'),
            'daily-insights' => TournamentCacheKeys::dailyInsights(1, '2026-10-09'),
            'weekly-leaders' => TournamentCacheKeys::weeklyLeaders(1, '2026-10-09'),
        }));
    }

    #[DataProvider('blocks')]
    public function test_season_spans_tournament_dates_and_scheduled_games(string $block): void
    {
        // Даты турнира 3 — сентябрь, но игра стоит на 5 октября: так бывает с плей-офф, и день игры обязан приниматься.
        foreach (['2026-09-01', '2026-09-30', '2026-10-05'] as $date) {
            $this->getJson("/api/tournaments/3/{$block}?date={$date}")->assertOk();
        }

        foreach (['2026-08-31', '2026-10-06'] as $date) {
            $this->getJson("/api/tournaments/3/{$block}?date={$date}")
                ->assertUnprocessable()
                ->assertJsonValidationErrors('date');
        }
    }

    #[DataProvider('blocks')]
    public function test_explicit_date_is_rejected_when_the_season_is_unknown(string $block): void
    {
        // Ни дат, ни игр — границ нет: принимается только день по умолчанию.
        $this->getJson("/api/tournaments/2/{$block}?date=2026-10-18")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');

        $this->getJson("/api/tournaments/2/{$block}")->assertOk();
    }

    public function test_weekly_leaders_accept_a_valid_date(): void
    {
        $this->getJson('/api/tournaments/1/weekly-leaders?date=2026-10-17')->assertOk();
    }

    public function test_daily_insights_default_to_last_game_day(): void
    {
        $response = $this->getJson('/api/tournaments/1/daily-insights')
            ->assertOk()
            ->assertJsonStructure(['date', 'data' => ['*' => ['slug', 'title']]]);

        $this->assertSame('2026-10-17', $response->json('date'));
        $this->assertIsArray($response->json('data'));
    }

    public function test_daily_insights_respect_explicit_date(): void
    {
        $response = $this->getJson('/api/tournaments/1/daily-insights?date=2026-10-10')->assertOk();

        $this->assertSame('2026-10-10', $response->json('date'));
    }

    public function test_daily_insights_without_games_return_empty_data(): void
    {
        $response = $this->getJson('/api/tournaments/2/daily-insights')->assertOk();

        $this->assertSame('2026-10-18', $response->json('date'));
        $this->assertSame([], $response->json('data'));
    }

    public function test_warm_job_caches_last_game_day(): void
    {
        (new WarmTournamentCacheJob(1))->handle(
            app(WeeklyLeadersService::class),
            app(TournamentService::class),
            app(DailyInsightEngine::class),
            app(RankingService::class),
        );

        $this->assertTrue(Cache::has(TournamentCacheKeys::playersOfTheDay(1, 5, true, '2026-10-17')));
        $this->assertTrue(Cache::has(TournamentCacheKeys::dailyInsights(1, '2026-10-17')));

        $this->assertSame('2026-10-17', $this->getJson('/api/tournaments/1/players-of-the-day')->json('date'));
    }

    public function test_game_import_warms_only_its_tournament_leaderboard(): void
    {
        // Два первых промаха по турнирам 1 и 2 и ещё два — прогрев турнира 1; остальные запросы читают кеш.
        $this->partialMock(RankingService::class)->shouldReceive('calculateBase')->times(4)->andReturn([]);
        $leaderboard = fn (int $tournamentId) => $this->getJson("/api/leaderboard?tournament_id={$tournamentId}&per=fullGame")->assertOk();

        $leaderboard(1);
        $leaderboard(2);
        $leaderboard(1);

        WarmTournamentCacheJob::dispatchSync(1);

        $leaderboard(1);
        $leaderboard(2);
    }

    public function test_warm_job_fills_the_leaderboard_base_for_the_dashboard(): void
    {
        $mock = $this->partialMock(RankingService::class);
        $mock->shouldReceive('calculateBase')->once()->with(1, 'fullGame', true)->andReturn([[1, 1, 600, 1.0]]);
        $mock->shouldReceive('calculateBase')->once()->with(1, 'fullGame', false)->andReturn([[1, 1, 600, 2.0]]);

        WarmTournamentCacheJob::dispatchSync(1);

        $version = Cache::get(TournamentCacheKeys::leaderboardVersion(1));
        $this->assertSame([[1, 1, 600, 1.0]], Cache::get(TournamentCacheKeys::leaderboardBase(1, 'fullGame', true, $version)));
        $this->assertSame([[1, 1, 600, 2.0]], Cache::get(TournamentCacheKeys::leaderboardBase(1, 'fullGame', false, $version)));
        $this->assertFalse(Cache::has(TournamentCacheKeys::leaderboardBase(1, 'bench', true, $version)));
    }

    public function test_warm_job_removes_bases_of_the_previous_version(): void
    {
        $this->partialMock(RankingService::class)->shouldReceive('calculateBase')->twice()->andReturn([]);
        Cache::put(TournamentCacheKeys::leaderboardVersion(1), 111, now()->addDay());
        Cache::put(TournamentCacheKeys::leaderboardBase(1, 'fullGame', false, 111), [[1, 1, 600, 1.0]], now()->addDay());
        Cache::put(TournamentCacheKeys::leaderboardBase(1, 'bench', true, 111), [[1, 1, 600, 1.0]], now()->addDay());

        WarmTournamentCacheJob::dispatchSync(1);

        $this->assertFalse(Cache::has(TournamentCacheKeys::leaderboardBase(1, 'fullGame', false, 111)));
        $this->assertFalse(Cache::has(TournamentCacheKeys::leaderboardBase(1, 'bench', true, 111)));
    }

    public function test_failed_base_calculation_keeps_the_previous_version(): void
    {
        $this->partialMock(RankingService::class)->shouldReceive('calculateBase')->andThrow(new \RuntimeException('boom'));
        Cache::put(TournamentCacheKeys::leaderboardVersion(1), 111, now()->addDay());
        Cache::put(TournamentCacheKeys::leaderboardBase(1, 'fullGame', true, 111), [[1, 1, 600, 1.0]], now()->addDay());

        $this->assertThrows(fn () => WarmTournamentCacheJob::dispatchSync(1), \RuntimeException::class);

        $this->assertSame(111, Cache::get(TournamentCacheKeys::leaderboardVersion(1)));
        $this->assertTrue(Cache::has(TournamentCacheKeys::leaderboardBase(1, 'fullGame', true, 111)));
    }

    public function test_game_saved_during_warm_can_queue_the_next_warm(): void
    {
        $lockKey = UniqueLock::getKey(new WarmTournamentCacheJob(1));
        $lockIsFreeDuringWarm = null;

        // Загрузка турнира — первое, что делает job, поэтому здесь видно состояние блокировки во время её работы.
        Tournament::retrieved(function () use ($lockKey, &$lockIsFreeDuringWarm) {
            $lockIsFreeDuringWarm ??= (bool) Cache::lock($lockKey, 1)->get();
        });

        WarmTournamentCacheJob::dispatch(1);

        $this->assertTrue($lockIsFreeDuringWarm);
    }
}
