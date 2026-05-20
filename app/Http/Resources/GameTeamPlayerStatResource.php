<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameTeamPlayerStatResource extends JsonResource
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
            'player_id' => $this->player_id,
            'plus_minus' => $this->plus_minus,
            'played_seconds' => $this->played_seconds,
            'points' => $this->points,
            'assists' => $this->assists,
            'rebounds' => $this->rebounds,
            'steals' => $this->steals,
            'blocks' => $this->blocks,
            'field_goals_percentage' => $this->field_goals_percentage,
            'turnovers' => $this->turnovers,
            'imp' => $this->imp ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'player' => new PlayerResource($this->whenLoaded('player')),
        ];
    }
}
