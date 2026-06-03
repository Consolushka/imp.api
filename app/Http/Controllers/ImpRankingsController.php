<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImpRankingRequest;
use App\Http\Resources\RankedPlayerResource;
use App\Service\Tournament\RankingService;
use Illuminate\Support\Facades\Cache;

class ImpRankingsController
{
    /**
     * @param ImpRankingRequest $request
     * @param RankingService $rankingService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<RankedPlayerResource>
     */
    public function index(ImpRankingRequest $request, RankingService $rankingService)
    {
        $cacheKey = 'leaderboard_' . md5(json_encode($request->validated()));

        $leaderboard = Cache::remember($cacheKey, now()->addDay(), function () use ($request, $rankingService) {
            return $rankingService->calculate($request);
        });

        $total = count($leaderboard);
        $limit = $request->getLimit();
        $slicedLeaderboard = array_slice($leaderboard, 0, $limit);

        return RankedPlayerResource::collection($slicedLeaderboard)
            ->additional(['meta' => ['total' => $total]]);
    }
}
