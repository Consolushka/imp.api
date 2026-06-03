<?php

namespace App\Http\Resources;

use App\Dtos\PlayerOfTheDayDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PlayerOfTheDayDto $resource
 */
class PlayerOfTheDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'full_name'      => $this->resource->fullName,
            'team_alias'     => $this->resource->teamAlias,
            'played_seconds' => $this->resource->playedSeconds,
            'pts'            => $this->resource->pts,
            'reb'            => $this->resource->reb,
            'ast'            => $this->resource->ast,
            'imp'            => $this->resource->imp,
        ];
    }
}
