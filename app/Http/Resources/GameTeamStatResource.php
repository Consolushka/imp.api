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
            'is_home' => $this->is_home,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'team' => new TeamResource($this->whenLoaded('team')),
            'playerStats' => GameTeamPlayerStatResource::collection($this->playerStats ?? []),
        ];
    }
}
