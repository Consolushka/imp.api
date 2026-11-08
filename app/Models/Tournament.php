<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $tier
*/
class Tournament extends Model
{
    use SoftDeletes;

    protected $table = 'tournaments';

    protected $fillable = [
        'league_id',
        'name',
        'start_at',
        'end_at',
        'regulation_duration',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'regulation_duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Пустая дата окончания значит, что Argus её не знает — такой турнир считаем идущим.
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()));
    }

    // Сезон для проверки запрошенной даты: даты турнира, а когда Argus их не знает — первая и последняя игра в расписании.
    public function coversDay(Carbon $day): bool
    {
        $start = $this->start_at ?? $this->games()->min('scheduled_at');
        $end = $this->end_at ?? $this->games()->max('scheduled_at');

        if ($start === null || $end === null) {
            return false;
        }

        return $day->between(Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay());
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function pollLogs(): HasMany
    {
        return $this->hasMany(TournamentPollLog::class);
    }

    public function latestPollLog()
    {
        return $this->hasOne(TournamentPollLog::class)->latestOfMany();
    }
}
