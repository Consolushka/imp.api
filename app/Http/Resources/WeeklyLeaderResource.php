<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyLeaderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $value = $this->resource['value'];
        $formattedValue = match ($this->resource['category']) {
            'points'   => number_format($value, 1) . ' PPG',
            'assists'  => number_format($value, 1) . ' APG',
            'rebounds' => number_format($value, 1) . ' RPG',
            'imp'      => ($value > 0 ? '+' : '') . number_format($value, 1) . ' IMP',
            default    => (string)$value,
        };

        return [
            'category'         => $this->resource['category'],
            'player_full_name' => $this->resource['player_full_name'],
            'value'            => $formattedValue,
        ];
    }
}
