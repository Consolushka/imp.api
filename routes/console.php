<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Кеш в БД сам просроченные записи не удаляет, пока их кто-то не прочитает: ключи с датами и фильтрами
// читаются один раз и остались бы в таблице навсегда.
Artisan::command('cache:prune-expired', function () {
    $store = config('cache.stores.'.config('cache.default'));

    // У другого хранилища таблицы кеша может не быть вовсе, а если она осталась — её записи уже никто не читает.
    if (($store['driver'] ?? null) !== 'database') {
        $this->info('Cache store is not database, nothing to prune.');

        return;
    }

    $deleted = DB::connection($store['connection'] ?? null)
        ->table($store['table'])
        ->where('expiration', '<=', now()->getTimestamp())
        ->delete();

    $this->info("Deleted {$deleted} expired cache entries.");
})->purpose('Delete expired entries from the database cache store')->hourly();
