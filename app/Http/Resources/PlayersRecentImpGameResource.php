<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayersRecentImpGameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'game_id' => $this->resource['game_id'],
            'scheduled_at' => $this->resource['scheduled_at'],
            'imp' => $this->resource['imp'],
        ];
    }
}
