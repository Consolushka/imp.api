<?php

namespace Tests\Feature;

use App\Jobs\WarmTournamentCacheJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AggregatorWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.aggregator.token' => 'secret']);

        Schema::create('tournaments', function ($table) {
            $table->id();
        });

        DB::table('tournaments')->insert([['id' => 1], ['id' => 2]]);

        Queue::fake();
    }

    private function gameImported(int $tournamentId): void
    {
        $this->postJson(
            '/api/webhooks/aggregator/game-imported',
            ['tournament_id' => $tournamentId],
            ['X-AGGREGATOR-TOKEN' => 'secret'],
        )->assertAccepted();
    }

    public function test_games_of_one_poll_queue_one_delayed_warm_per_tournament(): void
    {
        $this->gameImported(1);
        $this->gameImported(1);
        $this->gameImported(2);

        Queue::assertPushed(WarmTournamentCacheJob::class, 2);
        Queue::assertPushed(WarmTournamentCacheJob::class, fn (WarmTournamentCacheJob $job) => $job->delay !== null);
    }
}
