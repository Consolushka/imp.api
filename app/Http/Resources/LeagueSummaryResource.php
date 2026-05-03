<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property string $alias
 * @property int|null $tier
 * @property int $tournaments_count
 * @property int $games_count
 */
class LeagueSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'alias'             => $this->alias,
            'tier'              => $this->tier,
            'tournaments_count' => $this->tournaments_count,
            'games_count'       => $this->games_count,
        ];
    }
}
