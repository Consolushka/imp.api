<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ImpCalculateRawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'played_seconds' => ['required', 'integer', 'min:0'],
            'plus_minus' => ['required', 'integer'],
            'final_differential' => ['required', 'integer'],
            'duration' => ['required', 'integer', 'min:40'],
            'pers' => ['required', 'array'],
            'pers.*' => ['string', Rule::in(PersEnum::stringCases())],
            'use_reliability' => ['boolean'],
        ];
    }

    public function getPlayedSeconds(): int
    {
        return (int) $this->validated()['played_seconds'];
    }

    public function getPlusMinus(): int
    {
        return (int) $this->validated()['plus_minus'];
    }

    public function getFinalDifferential(): int
    {
        return (int) $this->validated()['final_differential'];
    }

    public function getDuration(): int
    {
        return (int) $this->validated()['duration'];
    }

    public function getPers(): array
    {
        return $this->validated()['pers'];
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }
}
