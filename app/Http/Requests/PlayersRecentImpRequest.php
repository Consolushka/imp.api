<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlayersRecentImpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<int> */
    public function getPlayerIds(): array
    {
        return $this->validated()['player_ids'];
    }

    public function getTournamentId(): int
    {
        return $this->validated()['tournament_id'];
    }

    public function getTeamId(): ?int
    {
        return $this->validated()['team_id'] ?? null;
    }

    public function getPer(): string
    {
        return $this->validated()['per'];
    }

    public function getOffset(): int
    {
        return $this->validated()['offset'] ?? 0;
    }

    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 15;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'player_ids' => 'required|array',
            'player_ids.*' => 'exists:players,id',
            'tournament_id' => 'required|exists:tournaments,id',
            'team_id' => 'exists:teams,id',
            'per' => 'required|in:' . implode(',', PersEnum::stringCases()),
            'offset' => 'integer|min:0',
            'limit' => 'integer|min:1|max:100',
        ];
    }
}
