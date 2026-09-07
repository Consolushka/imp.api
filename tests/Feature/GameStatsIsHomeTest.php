<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameTeamStat;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameStatsIsHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_first_inserted_team_stat_is_home(): void
    {
        $game = Game::create(['title' => 'HOME - AWAY']);
        $other = Game::create(['title' => 'X - Y']);
        // В другой игре строка вставлена раньше — не должна влиять на определение хозяина в первой.
        GameTeamStat::create(['game_id' => $other->id, 'team_id' => 9, 'score' => 1]);
        GameTeamStat::create(['game_id' => $game->id, 'team_id' => 1, 'score' => 80]);
        GameTeamStat::create(['game_id' => $game->id, 'team_id' => 2, 'score' => 70]);

        $stats = Game::with('gameTeamStats')->find($game->id)->gameTeamStats->keyBy('team_id');

        $this->assertTrue($stats[1]->is_home);
        $this->assertFalse($stats[2]->is_home);
        $this->assertSame([true, false], $stats->pluck('is_home')->all());

        Schema::create('teams', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('home_town')->nullable();
            $table->string('alias')->nullable();
            $table->timestamps();
        });
        Schema::create('game_team_player_stats', function ($table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('team_id');
            $table->integer('player_id');
        });

        $this->getJson("/api/games/{$game->id}/stats")
            ->assertOk()
            ->assertJsonPath('data.game_team_stats.0.team_id', 1)
            ->assertJsonPath('data.game_team_stats.0.is_home', true)
            ->assertJsonPath('data.game_team_stats.1.is_home', false);
    }
}
