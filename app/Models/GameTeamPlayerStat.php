<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $game_id
 * @property int $team_id
 * @property int $player_id
 * @property int $played_seconds
 * @property int $plus_minus
 * @property int points
 * @property int $rebounds
 * @property int $assists
 * @property int $steals
 * @property int $blocks
 * @property int $turnovers
 * @property Carbon $created_at
 * @property Carbon $updated_at
*/
class GameTeamPlayerStat extends Model
{
    protected $table = 'game_team_player_stats';

    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
        'played_seconds',
        'plus_minus',
        'points',
        'rebounds',
        'assists',
        'steals',
        'blocks',
        'turnovers',
    ];

    protected $casts = [
        'played_seconds' => 'integer',
        'plus_minus' => 'integer',
        'points' => 'integer',
        'rebounds' => 'integer',
        'assists' => 'integer',
        'steals' => 'integer',
        'blocks' => 'integer',
        'turnovers' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
