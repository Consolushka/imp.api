<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * @method static League find(int $league)
 * @property int $id
 * @property string $name
 * @property string $alias
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Tournament[] $tournaments
 */
class League extends Model
{
    protected $table = 'leagues';

    protected $fillable = [
        'name',
        'alias',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
