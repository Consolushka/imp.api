<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $duration
 * @property Collection<GameTeamStat> $gameTeamStats
*/
class Game extends Model
{
    protected $table = 'games';

    protected $fillable = [
        'tournament_id',
        'scheduled_at',
        'title',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function gameTeamStats(): HasMany
    {
        return $this->hasMany(GameTeamStat::class);
    }

    public function gameTeamPlayerStats(): HasMany
    {
        return $this->hasMany(GameTeamPlayerStat::class);
    }
}
