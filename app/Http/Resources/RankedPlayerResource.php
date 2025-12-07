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
            'player'      => [
                'id'        => $this->resource->getPlayerId(),
                'full_name' => $this->resource->getPlayer()->full_name,
                'birth_date_at' => $this->resource->getPlayer()->birth_date_at
            ],
            'games_count' => $this->resource->getGames(),
            'avg_imp'     => $this->resource->getAvgImp(),
        ];
    }
}
