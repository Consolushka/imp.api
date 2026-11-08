<?php

namespace App\Http\Requests;

class PlayersOfTheDayRequest extends TournamentDayRequest
{
    // Умолчания совпадают с тем, что заранее считает WarmTournamentCacheJob: с другими значениями его работа пропадала бы зря.
    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 5;
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('use_reliability')) {
            $this->merge([
                'use_reliability' => filter_var($this->input('use_reliability'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return parent::rules() + [
            'limit' => 'integer|min:1|max:100',
            'use_reliability' => 'boolean',
        ];
    }
}
