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
        $cacheKey = 'leaderboard_' . md5(json_encode($request->validated()));

        $leaderboard = Cache::remember($cacheKey, now()->addDay(), function () use ($request, $rankingService) {
            return $rankingService->calculate($request);
        });

        return RankedPlayerResource::collection($leaderboard);
    }
}
