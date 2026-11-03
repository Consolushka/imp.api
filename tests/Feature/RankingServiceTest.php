<?php

namespace Tests\Feature;

use App\Dtos\RankedPlayerDto;
use App\Dtos\RankingFilters;
use App\Service\Imp\Dtos\ImpPerDto;
use App\Service\Imp\ImpService;
use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RankingServiceTest extends TestCase
{
    private const IMP_BY_STAT_ID = [1 => 10.0, 2 => 20.0, 3 => 30.0, 4 => 50.0, 5 => 99.0];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('games', function ($table) {
            $table->id();
            $table->integer('tournament_id');
        });
        Schema::create('game_team_player_stats', function ($table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('team_id');
            $table->integer('player_id');
            $table->integer('played_seconds');
        });
        Schema::create('players', function ($table) {
            $table->id();
        });
        Schema::create('teams', function ($table) {
            $table->id();
            $table->string('alias');
        });

        DB::table('games')->insert([
            ['id' => 1, 'tournament_id' => 1],
            ['id' => 2, 'tournament_id' => 1],
            ['id' => 3, 'tournament_id' => 2],
        ]);
        DB::table('players')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('teams')->insert([['id' => 1, 'alias' => 'AAA'], ['id' => 2, 'alias' => 'BBB']]);
        DB::table('game_team_player_stats')->insert([
            // Игрок 1: две игры, средний IMP 15, в среднем 20 минут.
            ['id' => 1, 'game_id' => 1, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 1200],
            ['id' => 2, 'game_id' => 2, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 1200],
            // Игрок 2: одна игра, средний IMP 30, 10 минут.
            ['id' => 3, 'game_id' => 1, 'team_id' => 2, 'player_id' => 2, 'played_seconds' => 600],
            // Игрок 3 не должен попасть в рейтинг: другой турнир и игра без сыгранных минут.
            ['id' => 4, 'game_id' => 3, 'team_id' => 1, 'player_id' => 3, 'played_seconds' => 1200],
            ['id' => 5, 'game_id' => 1, 'team_id' => 2, 'player_id' => 3, 'played_seconds' => 0],
        ]);
    }

    private function filters(mixed ...$overrides): RankingFilters
    {
        return new RankingFilters(...array_merge([
            'tournamentId' => 1,
            'per' => 'fullGame',
            'order' => 'desc',
            'minGames' => 1,
            'teamId' => null,
            'minMinutes' => null,
            'avgMinutes' => null,
            'useReliability' => true,
        ], $overrides));
    }

    private function mockImps(string $per = 'fullGame', bool $useReliability = true): void
    {
        $this->mock(ImpService::class)
            ->shouldReceive('calcImpForStatIds')
            ->with(\Mockery::any(), [$per], $useReliability)
            ->andReturnUsing(fn (array $ids) => collect($ids)
                ->mapWithKeys(fn (int $id) => [$id => [$per => new ImpPerDto(self::IMP_BY_STAT_ID[$id])]])
                ->all());
    }

    public static function rankings(): array
    {
        return [
            'best first by default' => [[], [2, 1]],
            'worst first' => [['order' => 'asc'], [1, 2]],
            'other tournament' => [['tournamentId' => 2], [3]],
            'team' => [['teamId' => 1], [1]],
            'minimum games' => [['minGames' => 2], [1]],
            'minimum minutes in a game' => [['minMinutes' => 15], [1]],
            'minimum average minutes' => [['avgMinutes' => 15], [1]],
        ];
    }

    #[DataProvider('rankings')]
    public function test_filters_shape_the_ranking(array $overrides, array $expectedPlayerIds): void
    {
        $this->mockImps();

        $ranking = app(RankingService::class)->calculate($this->filters(...$overrides));

        $this->assertSame(
            $expectedPlayerIds,
            array_map(fn (RankedPlayerDto $player) => $player->getPlayerId(), $ranking)
        );
        $this->assertSame(
            range(1, count($expectedPlayerIds)),
            array_map(fn (RankedPlayerDto $player) => $player->getLeaderboardPosition(), $ranking)
        );
    }

    public function test_per_and_reliability_reach_the_imp_calculation(): void
    {
        $this->mockImps(per: 'clean', useReliability: false);

        $ranking = app(RankingService::class)->calculate($this->filters(per: 'clean', useReliability: false));

        $this->assertSame(30.0, $ranking[0]->getAvgImp());
    }
}
