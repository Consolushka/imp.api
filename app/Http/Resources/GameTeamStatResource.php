<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameTeamStatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'team_id' => $this->team_id,
            'score' => $this->score,
            'final_differential' => $this->final_differential,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'playerStats' => GameTeamPlayerStatResource::collection($this->playerStats ?? []),
        ];
    }
}
