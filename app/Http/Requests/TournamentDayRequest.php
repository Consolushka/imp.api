<?php

namespace App\Http\Requests;

use App\Models\Tournament;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class TournamentDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getDate(): ?Carbon
    {
        $date = $this->validated()['date'] ?? null;

        return $date === null ? null : Carbon::parse($date);
    }

    public function rules(): array
    {
        return [
            // Дата вне сезона не считается и не кешируется: иначе перебор дат растил бы таблицу cache без предела.
            'date' => ['bail', 'date', function (string $attribute, mixed $value, Closure $fail) {
                /** @var Tournament $tournament */
                $tournament = $this->route('tournament');

                if (! $tournament->coversDay(Carbon::parse($value))) {
                    $fail('The date must fall within the tournament season.');
                }
            }],
        ];
    }
}
