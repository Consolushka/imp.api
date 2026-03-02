<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlayerStatImpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<int> */
    public function getIds(): array
    {
        return $this->validated()['ids'];
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }

    /**
     * Handle boolean string values from GET request.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('use_reliability')) {
            $this->merge([
                'use_reliability' => filter_var($this->use_reliability, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids'             => 'required|array',
            'ids.*'           => 'exists:game_team_player_stats,id',
            'pers'            => 'required|array',
            'pers.*'          => 'in:' . implode(',', PersEnum::stringCases()),
            'use_reliability' => 'boolean',
        ];
    }
}
