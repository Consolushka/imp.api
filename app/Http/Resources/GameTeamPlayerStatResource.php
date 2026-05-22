<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property float|null $imp
 */
class GameTeamPlayerStatResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     game_id: int,
     *     team_id: int,
     *     player_id: int,
     *     plus_minus: int,
     *     played_seconds: int,
     *     points: int,
     *     assists: int,
     *     rebounds: int,
     *     steals: int,
     *     blocks: int,
     *     field_goals_percentage: string,
     *     turnovers: int,
     *     imp: float|null,
     *     created_at: string,
     *     updated_at: string,
     *     player: PlayerResource|\Illuminate\Http\Resources\MissingValue
     * }
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
            'field_goals_percentage' => (int) ($this->field_goals_percentage * 100) . '%',
            'turnovers' => $this->turnovers,
            'imp' => $this->imp !== null ? (float) $this->imp : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'player' => new PlayerResource($this->whenLoaded('player')),
        ];
    }
}
