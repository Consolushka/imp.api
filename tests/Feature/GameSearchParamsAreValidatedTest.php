<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GameSearchParamsAreValidatedTest extends TestCase
{
    public static function invalidParams(): array
    {
        return [
            'negative limit' => ['text=a&limit=-1', 'limit'],
            'zero limit' => ['text=a&limit=0', 'limit'],
            'limit above max' => ['text=a&limit=101', 'limit'],
            'non-numeric limit' => ['text=a&limit=abc', 'limit'],
            'empty limit' => ['text=a&limit=', 'limit'],
            'array text' => ['text[]=a', 'text'],
            'text above max length' => ['text='.str_repeat('a', 101), 'text'],
        ];
    }

    #[DataProvider('invalidParams')]
    public function test_invalid_params_are_rejected(string $query, string $field): void
    {
        $this->getJson("/api/games/search?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    // Поиск по тексту идёт через ilike, которого нет в sqlite, поэтому допустимые значения проверяем при пустом тексте: до запроса к базе дело не доходит.
    public static function validParamsWithoutText(): array
    {
        return [
            'no params' => [''],
            'empty text' => ['text='],
            'min limit' => ['limit=1'],
            'max limit' => ['limit=100'],
        ];
    }

    #[DataProvider('validParamsWithoutText')]
    public function test_valid_params_without_text_return_empty_list(string $query): void
    {
        $this->getJson("/api/games/search?{$query}")
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
