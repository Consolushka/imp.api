<?php

namespace App\Http\Resources;

use App\Dtos\DailyInsightDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DailyInsightDto $resource
 */
class DailyInsightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->resource->slug,
            'title' => $this->resource->title,
            'playerId' => $this->resource->playerId,
            'playerFullName' => $this->resource->playerFullName,
            'gameId' => $this->resource->gameId,
            'value' => $this->resource->value,
            'text' => $this->resource->text,
            'placeholders' => $this->resource->placeholders,
        ];
    }
}
