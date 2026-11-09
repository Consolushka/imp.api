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
use PHPUnit\Framework\Attributes\TestWith;
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

    /** @return array<RankedPlayerDto> */
    private function rank(RankingFilters $filters): array
    {
        $service = app(RankingService::class);

        return $service->hydrate($service->selectRanked(
            $service->calculateBase($filters->tournamentId, $filters->per, $filters->useReliability),
            $filters
        ));
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

    private function mockImps(string $per = 'fullGame', bool $useReliability = true, array $imps = self::IMP_BY_STAT_ID): void
    {
        $this->mock(ImpService::class)
            ->shouldReceive('calcImpForStatIds')
            ->with(\Mockery::any(), [$per], $useReliability)
            ->andReturnUsing(fn (array $ids) => collect($ids)
                ->mapWithKeys(fn (int $id) => [$id => [$per => new ImpPerDto($imps[$id])]])
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

        $ranking = $this->rank($this->filters(...$overrides));

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

        $ranking = $this->rank($this->filters(per: 'clean', useReliability: false));

        $this->assertSame(30.0, $ranking[0]->getAvgImp());
    }

    #[TestWith(['asc'])]
    #[TestWith(['desc'])]
    public function test_equal_averages_keep_order_whatever_the_row_order(string $order): void
    {
        $this->mockImps(imps: [1 => 5.0, 2 => 5.0]);

        foreach ([[1, 2], [2, 1]] as [$firstRowPlayerId, $secondRowPlayerId]) {
            DB::table('game_team_player_stats')->delete();
            DB::table('game_team_player_stats')->insert([
                ['id' => 1, 'game_id' => 1, 'team_id' => 1, 'player_id' => $firstRowPlayerId, 'played_seconds' => 600],
                ['id' => 2, 'game_id' => 1, 'team_id' => 1, 'player_id' => $secondRowPlayerId, 'played_seconds' => 600],
            ]);

            $ranking = $this->rank($this->filters(order: $order));

            $this->assertSame(
                [1, 2],
                array_map(fn (RankedPlayerDto $player) => $player->getPlayerId(), $ranking)
            );
        }
    }

    public function test_average_does_not_depend_on_read_order(): void
    {
        $this->mockImps(imps: [1 => 0.1, 2 => 0.2, 3 => 0.3]);

        DB::table('games')->insert(['id' => 4, 'tournament_id' => 1]);
        DB::table('game_team_player_stats')->delete();
        DB::table('game_team_player_stats')->insert([
            ['id' => 1, 'game_id' => 4, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 600],
            ['id' => 2, 'game_id' => 2, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 600],
            ['id' => 3, 'game_id' => 1, 'team_id' => 1, 'player_id' => 1, 'played_seconds' => 600],
        ]);
        // С индексом по игре база отдаёт строки по играм, то есть в обратном порядке id: так порядок чтения меняется без правки данных.
        Schema::table('game_team_player_stats', fn ($table) => $table->index('game_id'));
        $this->assertSame(
            [3, 2, 1],
            DB::table('game_team_player_stats')->whereIn('game_id', [1, 2, 4])->pluck('id')->all()
        );

        $ranking = $this->rank($this->filters());

        $this->assertSame((0.1 + 0.2 + 0.3) / 3, $ranking[0]->getAvgImp());
    }

    public function test_base_contains_played_rows_of_the_tournament_ordered_by_id(): void
    {
        $this->mockImps();

        $this->assertSame(
            [[1, 1, 1200, 10.0], [1, 1, 1200, 20.0], [2, 2, 600, 30.0]],
            app(RankingService::class)->calculateBase(1, 'fullGame', true)
        );
    }

    public function test_base_passes_per_and_reliability_to_the_imp_calculation(): void
    {
        $this->mockImps(per: 'clean', useReliability: false);

        $base = app(RankingService::class)->calculateBase(1, 'clean', false);

        $this->assertSame(10.0, $base[0][3]);
    }

    public function test_hydrate_builds_dtos_and_loads_only_requested_rows(): void
    {
        DB::enableQueryLog();

        $hydrated = app(RankingService::class)->hydrate([
            ['position' => 7, 'playerId' => 1, 'teamId' => 1, 'games' => 2, 'avgImp' => 15.0, 'avgPlayedSeconds' => 1200],
        ]);

        $playersQuery = collect(DB::getQueryLog())->first(fn (array $entry) => str_contains($entry['query'], 'players'));
        $this->assertSame([1], $playersQuery['bindings']);

        $this->assertCount(1, $hydrated);
        $this->assertSame(7, $hydrated[0]->getLeaderboardPosition());
        $this->assertSame(1, $hydrated[0]->getPlayerId());
        $this->assertSame('AAA', $hydrated[0]->getTeamAlias());
        $this->assertSame(2, $hydrated[0]->getGames());
        $this->assertSame(15.0, $hydrated[0]->getAvgImp());
        $this->assertSame(1200, $hydrated[0]->getAvgPlayedSeconds());
    }

    public function test_hydrate_uses_empty_alias_when_team_is_missing(): void
    {
        $hydrated = app(RankingService::class)->hydrate([
            ['position' => 1, 'playerId' => 1, 'teamId' => 99, 'games' => 1, 'avgImp' => 1.0, 'avgPlayedSeconds' => 600],
        ]);

        $this->assertSame('', $hydrated[0]->getTeamAlias());
    }
}
