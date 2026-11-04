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
            $table->boolean('is_home');
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
            $table->timestamps();
        });
    }

    public function test_is_home_comes_from_column_not_insert_order(): void
    {
        $gameId = Game::create(['title' => 'HOME - AWAY'])->id;
        // Гости вставлены раньше хозяев: по старому правилу «наименьший id» хозяевами стали бы они.
        GameTeamStat::create(['game_id' => $gameId, 'team_id' => 2, 'score' => 70, 'is_home' => false]);
        GameTeamStat::create(['game_id' => $gameId, 'team_id' => 1, 'score' => 80, 'is_home' => true]);

        foreach (["/api/games/{$gameId}", "/api/games/{$gameId}/stats"] as $uri) {
            $isHomeByTeam = collect($this->getJson($uri)->assertOk()->json('data.game_team_stats'))
                ->pluck('is_home', 'team_id');

            $this->assertTrue($isHomeByTeam[1], $uri);
            $this->assertFalse($isHomeByTeam[2], $uri);
        }
    }
}
