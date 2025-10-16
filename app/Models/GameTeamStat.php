<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
}
