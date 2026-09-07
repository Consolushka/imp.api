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
        // Хозяин площадки в БД не хранится. Argus всегда сохраняет статистику хозяев раньше гостей,
        // поэтому хозяин — строка с наименьшим id внутри игры. См. инвариант в контракте db-schema.
        // ponytail: неявный контракт на порядок вставки; надёжнее — колонка game_team_stats.is_home на стороне Argus.
        return $this->hasMany(GameTeamStat::class)
            ->selectRaw('game_team_stats.*, game_team_stats.id = (select min(id) from game_team_stats s where s.game_id = game_team_stats.game_id) as is_home');
    }

    public function gameTeamPlayerStats(): HasMany
    {
        return $this->hasMany(GameTeamPlayerStat::class);
    }
}
