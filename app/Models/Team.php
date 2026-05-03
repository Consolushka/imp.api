<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * @property string $alias
*/
class Team extends Model
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'home_town',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function gameTeamStats(): HasMany
    {
        return $this->hasMany(GameTeamStat::class);
    }

    public function gameTeamPlayerStats(): HasMany
    {
        return $this->hasMany(GameTeamPlayerStat::class);
    }
}
