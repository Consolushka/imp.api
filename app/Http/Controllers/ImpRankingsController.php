<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImpRankingRequest;
use App\Http\Resources\RankedPlayerResource;
use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\Cache;

class ImpRankingsController
{
    public function index(ImpRankingRequest $request, RankingService $rankingService)
    {
        // Страницы — срезы одного рейтинга: иначе каждая считала бы его заново, а позиции могли бы разъехаться.
        $cacheKey = 'leaderboard_'.md5(json_encode($request->safe()->except(['page', 'limit'])));

        $leaderboard = Cache::remember($cacheKey, now()->addDay(), function () use ($request, $rankingService) {
            return $rankingService->calculate($request);
        });

        // Смещение не вычисляем: на огромном page оно не помещается в int и запрос падал бы с ошибкой.
        $slicedLeaderboard = array_chunk($leaderboard, $request->getLimit())[$request->getPage() - 1] ?? [];

        return RankedPlayerResource::collection($slicedLeaderboard)
            ->additional(['meta' => ['total' => count($leaderboard)]]);
    }
}
