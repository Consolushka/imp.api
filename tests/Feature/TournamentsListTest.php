<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TournamentsListTest extends TestCase
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

        DB::table('tournaments')->insert([
            ['id' => 1, 'league_id' => 1, 'name' => 'Ongoing', 'end_at' => '2026-12-31 00:00:00', 'deleted_at' => null],
            ['id' => 2, 'league_id' => 1, 'name' => 'Finished', 'end_at' => '2026-05-31 00:00:00', 'deleted_at' => null],
            ['id' => 3, 'league_id' => 1, 'name' => 'No end date', 'end_at' => null, 'deleted_at' => null],
            ['id' => 4, 'league_id' => 1, 'name' => 'Deleted ongoing', 'end_at' => '2026-12-31 00:00:00', 'deleted_at' => '2026-10-01 00:00:00'],
            ['id' => 5, 'league_id' => 1, 'name' => 'Ends today later', 'end_at' => '2026-10-18 18:00:00', 'deleted_at' => null],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function ids(string $uri): array
    {
        return collect($this->getJson($uri)->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    }

    public function test_returns_only_active_tournaments_by_default(): void
    {
        $this->assertSame([1, 3, 5], $this->ids('/api/tournaments'));
    }

    public function test_all_false_returns_only_active_tournaments(): void
    {
        $this->assertSame([1, 3, 5], $this->ids('/api/tournaments?all=false'));
        $this->assertSame([1, 3, 5], $this->ids('/api/tournaments?all=0'));
    }

    public function test_all_true_includes_finished_tournaments(): void
    {
        $this->assertSame([1, 2, 3, 5], $this->ids('/api/tournaments?all=true'));
        $this->assertSame([1, 2, 3, 5], $this->ids('/api/tournaments?all=1'));
    }

    public function test_rejects_non_boolean_all(): void
    {
        $this->getJson('/api/tournaments?all=maybe')->assertUnprocessable()->assertJsonValidationErrors('all');
    }
}
