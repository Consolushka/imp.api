<?php

namespace App\Http\Resources;

use App\Dtos\RankedPlayerDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property RankedPlayerDto $resource
 */
class RankedPlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'position'    => $this->resource->getLeaderboardPosition(),
            'player'      => new PlayerResource($this->resource->getPlayer()),
            'team_alias'  => $this->resource->getTeamAlias(),
            'games_count' => $this->resource->getGames(),
            'avg_imp'     => $this->resource->getAvgImp(),
            'avg_played_seconds' => $this->resource->getAvgPlayedSeconds(),
        ];
    }
}
