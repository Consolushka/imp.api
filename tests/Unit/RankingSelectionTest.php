<?php

namespace Tests\Unit;

use App\Dtos\RankingFilters;
use App\Service\Imp\ImpService;
use App\Service\Tournament\RankingService;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class RankingSelectionTest extends TestCase
{
    private function service(): RankingService
    {
        return new RankingService(new ImpService);
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

    public function test_min_minutes_drops_games_before_grouping(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 10.0], [1, 1, 1800, 30.0]],
            $this->filters(minMinutes: 15)
        );

        $this->assertCount(1, $ranked);
        $this->assertSame(1, $ranked[0]['games']);
        $this->assertSame(30.0, $ranked[0]['avgImp']);
        $this->assertSame(1800, $ranked[0]['avgPlayedSeconds']);
    }

    public function test_min_games_counts_only_games_left_after_min_minutes(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 10.0], [1, 1, 1800, 30.0]],
            $this->filters(minMinutes: 15, minGames: 2)
        );

        $this->assertSame([], $ranked);
    }

    public function test_avg_minutes_uses_games_left_after_min_minutes(): void
    {
        $rows = [[1, 1, 600, 10.0], [1, 1, 1800, 30.0]];

        $this->assertCount(1, $this->service()->selectRanked($rows, $this->filters(minMinutes: 15, avgMinutes: 25)));
        $this->assertSame([], $this->service()->selectRanked($rows, $this->filters(avgMinutes: 25)));
    }

    public function test_team_filter_keeps_only_that_team(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 10.0], [1, 2, 600, 20.0]],
            $this->filters(teamId: 2)
        );

        $this->assertCount(1, $ranked);
        $this->assertSame(2, $ranked[0]['teamId']);
    }

    public function test_player_is_ranked_separately_for_each_team(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 10.0], [1, 2, 600, 20.0]],
            $this->filters()
        );

        $this->assertCount(2, $ranked);
    }

    public function test_order_controls_direction(): void
    {
        $rows = [[1, 1, 600, 10.0], [2, 1, 600, 20.0], [3, 1, 600, 30.0]];

        $this->assertSame(
            [1, 2, 3],
            array_column($this->service()->selectRanked($rows, $this->filters(order: 'asc')), 'playerId')
        );
        $this->assertSame(
            [3, 2, 1],
            array_column($this->service()->selectRanked($rows, $this->filters(order: 'desc')), 'playerId')
        );
    }

    #[TestWith(['asc'])]
    #[TestWith(['desc'])]
    public function test_equal_averages_are_ordered_by_string_key_in_both_directions(string $order): void
    {
        $ranked = $this->service()->selectRanked(
            [[2, 1, 600, 5.0], [10, 1, 600, 5.0]],
            $this->filters(order: $order)
        );

        $this->assertSame([10, 2], array_column($ranked, 'playerId'));
    }

    public function test_average_adds_imps_in_row_order(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 0.1], [1, 1, 600, 0.2], [1, 1, 600, 0.3]],
            $this->filters()
        );

        $this->assertSame((0.1 + 0.2 + 0.3) / 3, $ranked[0]['avgImp']);
    }

    public function test_positions_are_consecutive_from_one(): void
    {
        $ranked = $this->service()->selectRanked(
            [[1, 1, 600, 10.0], [2, 1, 600, 20.0], [3, 1, 600, 30.0]],
            $this->filters()
        );

        $this->assertSame([1, 2, 3], array_column($ranked, 'position'));
    }

    public function test_thresholds_keep_a_game_exactly_at_the_limit(): void
    {
        $rows = [[1, 1, 900, 1.0]];

        $this->assertCount(1, $this->service()->selectRanked($rows, $this->filters(minMinutes: 15)));
        $this->assertCount(1, $this->service()->selectRanked($rows, $this->filters(avgMinutes: 15)));
    }

    public function test_empty_rows_give_empty_ranking(): void
    {
        $this->assertSame([], $this->service()->selectRanked([], $this->filters()));
    }
}
