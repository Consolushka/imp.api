<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'totalDataPoints' => $this->resource['totalDataPoints'],
            'activeLeagues'   => $this->resource['activeLeagues'],
            'trackedPlayers'  => $this->resource['trackedPlayers'],
            'totalMatches'    => $this->resource['totalMatches'],
        ];
    }
}
