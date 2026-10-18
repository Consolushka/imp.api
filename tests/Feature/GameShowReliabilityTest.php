<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\GameTeamStat;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\PersEnum;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameShowReliabilityTest extends TestCase
{
    private const IMP_PATH = 'data.game_team_stats.0.playerStats.0.imp';

    private int $gameId;

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

        $this->gameId = Game::create(['title' => 'HOME - AWAY'])->id;
        GameTeamStat::create(['game_id' => $this->gameId, 'team_id' => 1, 'score' => 80, 'final_differential' => 0]);
        // Пять минут на площадке — надёжность заметно меньше единицы, разница между режимами видна.
        GameTeamPlayerStat::create([
            'game_id' => $this->gameId,
            'team_id' => 1,
            'player_id' => 1,
            'played_seconds' => 300,
            'plus_minus' => 10,
        ]);
    }

    public function test_reliability_is_applied_by_default(): void
    {
        $this->assertImp("/api/games/{$this->gameId}", $this->expectedImp(true));
    }

    public function test_use_reliability_false_disables_reliability(): void
    {
        $this->assertNotEquals($this->expectedImp(true), $this->expectedImp(false));

        $this->assertImp("/api/games/{$this->gameId}?use_reliability=false", $this->expectedImp(false));
    }

    public function test_legacy_reliability_param_is_still_accepted(): void
    {
        $this->assertImp("/api/games/{$this->gameId}?reliability=false", $this->expectedImp(false));
    }

    public function test_use_reliability_wins_over_legacy_param(): void
    {
        $this->assertImp("/api/games/{$this->gameId}?use_reliability=true&reliability=false", $this->expectedImp(true));
    }

    public function test_invalid_use_reliability_is_rejected(): void
    {
        $this->getJson("/api/games/{$this->gameId}?use_reliability=maybe")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('use_reliability');
    }

    private function assertImp(string $uri, float $expected): void
    {
        // Целое значение IMP уходит в JSON без дробной части, поэтому сравниваем числа, а не их запись.
        $actual = $this->getJson($uri)->assertOk()->json(self::IMP_PATH);
        $this->assertEqualsWithDelta($expected, $actual, 0.0001);
    }

    private function expectedImp(bool $useReliability): float
    {
        return ImpCalculator::evaluatePer(300, 10, 0, 40, PersEnum::FullGame, $useReliability);
    }
}
