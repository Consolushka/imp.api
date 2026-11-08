<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Кеш в БД сам просроченные записи не удаляет, пока их кто-то не прочитает: ключи с датами и фильтрами
// читаются один раз и остались бы в таблице навсегда.
Artisan::command('cache:prune-expired', function () {
    $deleted = DB::connection(config('cache.stores.database.connection'))
        ->table(config('cache.stores.database.table'))
        ->where('expiration', '<=', now()->getTimestamp())
        ->delete();

    $this->info("Deleted {$deleted} expired cache entries.");
})->purpose('Delete expired entries from the database cache store')->hourly();
