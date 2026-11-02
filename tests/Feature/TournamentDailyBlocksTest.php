<?php

namespace Tests\Feature;

use App\Jobs\WarmTournamentCacheJob;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Tournament\TournamentCacheKeys;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WeeklyLeadersService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        );

        $this->assertTrue(Cache::has(TournamentCacheKeys::playersOfTheDay(1, 5, true, '2026-10-17')));
        $this->assertTrue(Cache::has(TournamentCacheKeys::dailyInsights(1, '2026-10-17')));

        $this->assertSame('2026-10-17', $this->getJson('/api/tournaments/1/players-of-the-day')->json('date'));
    }
}
