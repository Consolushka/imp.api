<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Foundation\Http\FormRequest;

class GameShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function per(): PersEnum
    {
        return PersEnum::tryFrom($this->validated()['per'] ?? '') ?? PersEnum::FullGame;
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }

    protected function prepareForValidation(): void
    {
        // ponytail: старое имя параметра принимаем, пока dashboard не перешёл на use_reliability (VUE-5); убрать после её релиза.
        if (! $this->has('use_reliability') && $this->has('reliability')) {
            $this->merge(['use_reliability' => $this->input('reliability')]);
        }

        if ($this->has('use_reliability')) {
            $this->merge([
                'use_reliability' => filter_var($this->input('use_reliability'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'per' => 'in:'.implode(',', PersEnum::stringCases()),
            'use_reliability' => 'boolean',
        ];
    }
}
