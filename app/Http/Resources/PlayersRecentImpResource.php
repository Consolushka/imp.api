<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayersRecentImpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'player_id' => $this->resource['player_id'],
            'games' => PlayersRecentImpGameResource::collection($this->resource['games']),
            'meta' => $this->resource['meta'],
        ];
    }
}
