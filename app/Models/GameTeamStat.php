<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameTeamStat extends Model
{
    protected $table = 'game_team_stats';

    protected $fillable = [
        'game_id',
        'team_id',
        'score',
        'final_differential',
    ];

    protected $casts = [
        'score' => 'integer',
        'final_differential' => 'integer',
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

    public function gameTeamPlayerStats(): BelongsToMany
    {
        return $this->belongsToMany(GameTeamPlayerStat::class, 'game_team_player_stats', 'game_id')
            ->where('team_id', $this->team_id);
    }
}
