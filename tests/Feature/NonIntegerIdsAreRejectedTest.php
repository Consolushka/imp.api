<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NonIntegerIdsAreRejectedTest extends TestCase
{
    private const LEADERBOARD = '/api/leaderboard?per=fullGame';

    private const RECENT = '/api/players/imp/recent';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tournaments', 'teams', 'players', 'game_team_player_stats'] as $table) {
            Schema::create($table, function ($blueprint) {
                $blueprint->id();
            });

            DB::table($table)->insert(['id' => 1]);
        }
    }

    public static function invalidIds(): array
    {
        $recent = ['player_ids' => [1], 'tournament_id' => 1, 'per' => 'fullGame'];

        return [
            'leaderboard text tournament' => ['GET', self::LEADERBOARD.'&tournament_id=abc', [], 'tournament_id'],
            'leaderboard array tournament' => ['GET', self::LEADERBOARD.'&tournament_id[]=1', [], 'tournament_id'],
            'leaderboard text team' => ['GET', self::LEADERBOARD.'&tournament_id=1&team_id=abc', [], 'team_id'],
            'leaderboard array team' => ['GET', self::LEADERBOARD.'&tournament_id=1&team_id[]=1', [], 'team_id'],
            'recent text tournament' => ['POST', self::RECENT, ['tournament_id' => 'abc'] + $recent, 'tournament_id'],
            'recent array tournament' => ['POST', self::RECENT, ['tournament_id' => [1]] + $recent, 'tournament_id'],
            'recent text team' => ['POST', self::RECENT, ['team_id' => 'abc'] + $recent, 'team_id'],
            'recent array team' => ['POST', self::RECENT, ['team_id' => [1]] + $recent, 'team_id'],
            'recent text player' => ['POST', self::RECENT, ['player_ids' => ['abc']] + $recent, 'player_ids.0'],
            'imp text id' => ['GET', '/api/imp?pers[]=fullGame&ids[]=abc', [], 'ids.0'],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_invalid_id_is_rejected_before_reaching_the_database(string $method, string $uri, array $payload, string $field): void
    {
        DB::enableQueryLog();

        $this->json($method, $uri, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        // Postgres падает на сравнении числовой колонки с текстом, а sqlite нет, поэтому проверяем сам факт запроса.
        $this->assertNotContains('abc', collect(DB::getQueryLog())->pluck('bindings')->flatten()->all());
    }
}
