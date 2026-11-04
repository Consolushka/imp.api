<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class PlayersOfTheDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Умолчания совпадают с тем, что заранее считает WarmTournamentCacheJob: с другими значениями его работа пропадала бы зря.
    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 5;
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }

    public function getDate(): ?Carbon
    {
        $date = $this->validated()['date'] ?? null;

        return $date === null ? null : Carbon::parse($date);
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
        return [
            'limit' => 'integer|min:1|max:100',
            'date' => 'date',
            'use_reliability' => 'boolean',
        ];
    }
}
