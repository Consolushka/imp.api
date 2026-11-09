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

    // Сезон для проверки запрошенной даты — от самой ранней до самой поздней отметки среди дат турнира и игр в расписании.
    // Одним датам турнира верить нельзя: Argus часто знает их неточно, и плей-офф или первые туры оказываются за их пределами.
    public function coversDay(Carbon $day): bool
    {
        $games = $this->games()
            ->toBase()
            ->selectRaw('min(scheduled_at) as first_game, max(scheduled_at) as last_game')
            ->first();

        $marks = array_map(
            fn ($mark) => Carbon::parse($mark),
            array_filter([$this->start_at, $this->end_at, $games->first_game, $games->last_game])
        );

        if ($marks === []) {
            return false;
        }

        return $day->between(min($marks)->copy()->startOfDay(), max($marks)->copy()->endOfDay());
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
