<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PruneExpiredCacheTest extends TestCase
{
    private function seedCacheTable(): void
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
    }

    public function test_only_expired_entries_are_deleted(): void
    {
        config(['cache.default' => 'database']);
        $this->seedCacheTable();

        $this->artisan('cache:prune-expired')
            ->expectsOutput('Deleted 2 expired cache entries.')
            ->assertSuccessful();

        $this->assertSame(['live'], DB::table('cache')->pluck('key')->all());
    }

    public function test_other_cache_stores_are_left_alone(): void
    {
        // В тестах кеш лежит в памяти: таблицу команда трогать не должна, даже если она есть.
        $this->seedCacheTable();

        $this->artisan('cache:prune-expired')
            ->expectsOutput('Cache store is not database, nothing to prune.')
            ->assertSuccessful();

        $this->assertSame(3, DB::table('cache')->count());
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
