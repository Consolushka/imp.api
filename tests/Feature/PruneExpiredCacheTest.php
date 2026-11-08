<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PruneExpiredCacheTest extends TestCase
{
    public function test_only_expired_entries_are_deleted(): void
    {
        Schema::create('cache', function ($table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        DB::table('cache')->insert([
            ['key' => 'expired', 'value' => 'x', 'expiration' => now()->subMinute()->getTimestamp()],
            ['key' => 'expiring now', 'value' => 'x', 'expiration' => now()->getTimestamp()],
            ['key' => 'live', 'value' => 'x', 'expiration' => now()->addMinute()->getTimestamp()],
        ]);

        $this->artisan('cache:prune-expired')
            ->expectsOutput('Deleted 2 expired cache entries.')
            ->assertSuccessful();

        $this->assertSame(['live'], DB::table('cache')->pluck('key')->all());
    }

    public function test_pruning_is_scheduled_hourly(): void
    {
        // Любой вызов artisan загружает routes/console.php вместе с расписанием.
        $this->artisan('schedule:list')->run();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'cache:prune-expired'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
