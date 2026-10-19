<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TournamentsListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // В строке запроса приходит "true"/"false", а стандартное правило boolean их не принимает — переводим заранее.
    protected function prepareForValidation(): void
    {
        if ($this->has('all')) {
            $this->merge(['all' => filter_var($this->query('all'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }
    }

    public function includeFinished(): bool
    {
        return $this->validated('all') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all' => 'boolean',
        ];
    }
}
