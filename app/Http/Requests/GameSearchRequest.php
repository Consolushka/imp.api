<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getText(): ?string
    {
        return $this->validated()['text'] ?? null;
    }

    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 10;
    }

    public function rules(): array
    {
        return [
            // Пустая строка приходит как null и должна давать пустой список, а не ошибку.
            'text' => 'nullable|string|max:100',
            'limit' => 'integer|min:1|max:100',
        ];
    }
}
