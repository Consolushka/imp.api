<?php

namespace App\Http\Resources;

use App\Service\Imp\Dtos\ImpPerDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Service\Imp\Dtos\ImpDto $resource
 */
class ImpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'player_stat_id' => $this->resource->gameTeamPlayerStatId,
            'imp_pers'       => array_map(fn(ImpPerDto $impPerData) => [
                'per' => $impPerData->per,
                'imp' => $impPerData->imp,
            ], $this->resource->perDto),
        ];
    }
}
