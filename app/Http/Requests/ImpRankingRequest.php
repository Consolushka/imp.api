<?php

namespace App\Http\Requests;

use App\Service\Imp\PersEnum;
use Illuminate\Foundation\Http\FormRequest;

class ImpRankingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getTournamentId(): int
    {
        return $this->validated()['tournament_id'];
    }

    public function getPer(): string
    {
        return $this->validated()['per'];
    }

    public function getLimit(): int
    {
        return $this->validated()['limit'] ?? 10;
    }

    public function getPage(): int
    {
        return $this->validated()['page'] ?? 1;
    }

    public function getOrder(): string
    {
        return $this->validated()['order'] ?? 'desc';
    }

    public function getMinGames(): int
    {
        return $this->validated()['min_games'] ?? 1;
    }

    public function getTeamId(): ?int
    {
        return $this->validated()['team_id'] ?? null;
    }

    public function getMinMinutes(): ?int
    {
        return $this->validated()['min_minutes'] ?? null;
    }

    public function getAvgMinutes(): ?int
    {
        return $this->validated()['avg_minutes'] ?? null;
    }

    public function useReliability(): bool
    {
        return (bool) ($this->validated()['use_reliability'] ?? true);
    }

    /**
     * Всё, от чего зависит рейтинг: по этим значениям его ищут в кеше. Умолчания уже применены,
     * поэтому запрос без параметра и с его значением по умолчанию читают одну запись.
     * Новый фильтр рейтинга обязательно добавлять сюда, иначе запросы с разными его значениями получат один рейтинг.
     */
    public function rankingFilters(): array
    {
        return [
            'tournament_id' => $this->getTournamentId(),
            'per' => $this->getPer(),
            'order' => $this->getOrder(),
            'min_games' => $this->getMinGames(),
            'team_id' => $this->getTeamId(),
            'min_minutes' => $this->getMinMinutes(),
            'avg_minutes' => $this->getAvgMinutes(),
            'use_reliability' => $this->useReliability(),
        ];
    }

    /**
     * Handle boolean string values from GET request.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('use_reliability')) {
            $this->merge([
                'use_reliability' => filter_var($this->use_reliability, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tournament_id'   => 'required|integer|exists:tournaments,id',
            'per'             => 'required|in:' . implode(',', PersEnum::stringCases()),
            'limit'           => 'integer|min:1|max:100',
            'page'            => 'integer|min:1',
            'order'           => 'in:asc,desc',
            'min_games'       => 'integer|min:1',
            'team_id'         => 'integer|exists:teams,id',
            'min_minutes'     => 'integer|min:1',
            'avg_minutes'     => 'integer|min:1',
            'use_reliability' => 'boolean',
        ];
    }
}
