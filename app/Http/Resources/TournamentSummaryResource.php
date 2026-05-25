<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = now();
        $status = 'ongoing';
        if ($this->start_at && $now < $this->start_at) {
            $status = 'upcoming';
        } elseif ($this->end_at && $now > $this->end_at) {
            $status = 'completed';
        }

        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'games_count'           => $this->games_count ?? 0,
            'teams_count'           => (int) ($this->teams_count ?? 0),
            'best_player_full_name' => $this->best_player_full_name ?? 'John Doe', // Placeholder
            'next_update_at'        => ($this->next_update_at ?? now()->addHours(2))
                ->toDateTimeString(),
            'status'                => $status,
        ];
    }
}
