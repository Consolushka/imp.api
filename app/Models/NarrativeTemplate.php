<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class NarrativeTemplate extends Model
{
    protected $table = 'narrative_templates';

    protected $fillable = [
        'slug',
        'body',
    ];

    protected $casts = [
        'slug' => 'string',
        'body' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
