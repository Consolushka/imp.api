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
            'player_full_name' => $this['player_full_name'],
            'team_id' => $this['team_id'],
            'narrative' => $this['narrative'],
        ];
    }
}
