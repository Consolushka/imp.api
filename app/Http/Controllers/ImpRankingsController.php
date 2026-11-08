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
        // Страницы — срезы одного рейтинга: иначе каждая считала бы его заново, а позиции могли бы разъехаться.
        $filters = $request->rankingFilters();
        // После импорта игры прогрев меняет версию, и рейтинг, посчитанный до импорта, больше не читается.
        $version = Cache::get(TournamentCacheKeys::leaderboardVersion($filters->tournamentId), 0);
        $cacheKey = TournamentCacheKeys::leaderboard($filters, $version);

        $leaderboard = Cache::remember($cacheKey, now()->addDay(), function () use ($filters, $rankingService) {
            return $rankingService->calculate($filters);
        });

        // Смещение не вычисляем: на огромном page оно не помещается в int и запрос падал бы с ошибкой.
        $slicedLeaderboard = array_chunk($leaderboard, $request->getLimit())[$request->getPage() - 1] ?? [];

        return RankedPlayerResource::collection($slicedLeaderboard)
            ->additional(['meta' => ['total' => count($leaderboard)]]);
    }
}
