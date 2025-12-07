<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Foundation\Http\FormRequest;

class ImpRankingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getTournamentId(): int
    {
        return $this->validated()['tournament_id'];
    }

    public function getPer(): string
    {
        return $this->validated()['per'];
    }

    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 10;
    }

    public function getOrder(): string
    {
        return $this->validated()['order'] ?? 'desc';
    }

    public function getMinGames(): int
    {
        return $this->validated()['min_games'] ?? 1;
    }

    public function getTeamId(): ?int
    {
        return $this->validated()['team_id'] ?? null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tournament_id' => 'required|exists:tournaments,id',
            'per'        => 'required|in:' . implode(',', PersEnum::stringCases()),
            'limit'         => 'integer|min:1|max:100',
            'order'         => 'in:asc,desc',
            'min_games'     => 'integer|min:1',
            'team_id'       => 'exists:teams,id',
        ];
    }
}
