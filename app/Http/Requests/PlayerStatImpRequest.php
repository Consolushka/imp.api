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

    public function getIds()
    {
        return $this->validated()['ids'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids'    => 'required|array',
            'ids.*'  => 'exists:game_team_player_stats,id',
            'pers'   => 'required|array',
            'pers.*' => 'in:' . implode(',', PersEnum::stringCases()),
        ];
    }
}
