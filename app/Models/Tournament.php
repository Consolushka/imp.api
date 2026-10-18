<?php

namespace App\Models;

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
