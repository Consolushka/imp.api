<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerKeyPerformanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'player_id' => $this['player_id'],
            'player_name' => $this['player_name'],
            'team_id' => $this['team_id'],
            'narratives' => $this['narratives'],
        ];
    }
}
