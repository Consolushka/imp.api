<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
