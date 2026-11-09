<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImpRankingRequest;
use App\Http\Resources\RankedPlayerResource;
use App\Service\Tournament\RankingService;
use App\Service\Tournament\TournamentCacheKeys;
use Illuminate\Support\Facades\Cache;

class ImpRankingsController
{
    public function index(ImpRankingRequest $request, RankingService $rankingService)
    {
        $filters = $request->rankingFilters();
        // После импорта игры прогрев меняет версию, и база, посчитанная до импорта, больше не читается.
        $version = Cache::get(TournamentCacheKeys::leaderboardVersion($filters->tournamentId), 0);
        $cacheKey = TournamentCacheKeys::leaderboardBase($filters->tournamentId, $filters->per, $filters->useReliability, $version);

        // Фильтры и страницы применяются в памяти к одной записи кеша: иначе каждое их сочетание считалось бы и хранилось отдельно.
        $base = Cache::remember($cacheKey, now()->addDay(), function () use ($filters, $rankingService) {
            return $rankingService->calculateBase($filters->tournamentId, $filters->per, $filters->useReliability);
        });

        $ranked = $rankingService->selectRanked($base, $filters);

        // Смещение не вычисляем: на огромном page оно не помещается в int и запрос падал бы с ошибкой.
        $page = array_chunk($ranked, $request->getLimit())[$request->getPage() - 1] ?? [];

        return RankedPlayerResource::collection($rankingService->hydrate($page))
            ->additional(['meta' => ['total' => count($ranked)]]);
    }
}
