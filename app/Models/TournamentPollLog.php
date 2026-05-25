<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPollLog extends Model
{
    protected $table = 'tournament_poll_logs';

    protected $fillable = [
        'tournament_id',
        'poll_start_at',
        'poll_end_at',
        'interval_start',
        'interval_end',
        'saved_games_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'poll_start_at' => 'datetime',
        'poll_end_at' => 'datetime',
        'interval_start' => 'datetime',
        'interval_end' => 'datetime',
        'saved_games_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
