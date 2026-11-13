<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameTeamStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameSearchByTeamTest extends TestCase
{
    private int $realOlympiacosId;

    private int $unrelatedId;

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
            $table->integer('score')->nullable();
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

        // Название игры — пара алиасов, поэтому имя команды в нём не встречается.
        $this->realOlympiacosId = $this->makeGame('REA - OLY', [
            ['Real Madrid', 'REA', true],
            ['Olympiacos', 'OLY', false],
        ]);
        $this->unrelatedId = $this->makeGame('UNIT - TAMBOV', [
            ['Юнит', 'UNIT', true],
            ['Тамбов', 'TAMBOV', false],
        ]);
    }

    /**
     * @param  array<int, array{string, string, bool}>  $teams  имя, алиас, хозяева
     */
    private function makeGame(string $title, array $teams): int
    {
        $gameId = Game::create(['title' => $title])->id;

        foreach ($teams as [$name, $alias, $isHome]) {
            $teamId = DB::table('teams')->insertGetId(['name' => $name, 'alias' => $alias]);
            GameTeamStat::create(['game_id' => $gameId, 'team_id' => $teamId, 'is_home' => $isHome]);
        }

        return $gameId;
    }

    private function searchIds(string $text): array
    {
        return collect($this->getJson('/api/games/search?text='.urlencode($text))->assertOk()->json('data'))
            ->pluck('id')
            ->all();
    }

    public function test_finds_game_by_home_team_name_ignoring_case(): void
    {
        $this->assertSame([$this->realOlympiacosId], $this->searchIds('real madrid'));
    }

    public function test_finds_game_by_away_team_name(): void
    {
        $this->assertSame([$this->realOlympiacosId], $this->searchIds('Olympiacos'));
        $this->assertSame([$this->unrelatedId], $this->searchIds('Тамбов'));
    }

    public function test_finds_game_by_team_alias(): void
    {
        $this->assertSame([$this->unrelatedId], $this->searchIds('UNIT'));
    }

    public function test_finds_game_by_full_title(): void
    {
        $this->assertSame([$this->realOlympiacosId], $this->searchIds('REA - OLY'));
    }

    public function test_game_is_returned_once_when_title_name_and_alias_all_match(): void
    {
        $this->assertSame([$this->realOlympiacosId], $this->searchIds('rea'));
    }

    public function test_unknown_text_returns_nothing(): void
    {
        $this->assertSame([], $this->searchIds('Zenit'));
    }
}
