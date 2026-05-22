<?php

namespace App\Jobs;

use App\Http\Resources\PlayerOfTheDayResource;
use App\Http\Resources\WeeklyLeaderResource;
use App\Models\Tournament;
use App\Service\Tournament\RankingService;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WeeklyLeadersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class WarmTournamentCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $tournamentId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        WeeklyLeadersService $weeklyLeadersService,
        TournamentService $tournamentService
    ): void {
        $tournament = Tournament::find($this->tournamentId);
        if (!$tournament) {
            return;
        }

        // 1. Warm Best Player (24h)
        $bestPlayer = $tournamentService->getBestPlayerFullName($tournament);
        Cache::put("tournament_{$this->tournamentId}_best_player", $bestPlayer, now()->addDay());

        // 2. Warm Weekly Leaders (End of day UTC)
        $referenceDate = now();
        $dateStr = $referenceDate->toDateString();
        $leaders = $weeklyLeadersService->calculateLeaders($this->tournamentId, $referenceDate);
        $leadersResource = WeeklyLeaderResource::collection($leaders);
        Cache::put("tournament_{$this->tournamentId}_weekly_leaders_{$dateStr}", $leadersResource, now()->timezone('UTC')->endOfDay());

        // 3. Warm Players of the Day (End of day UTC)
        // We warm with default parameters: limit=5, use_reliability=true
        $playersOfTheDay = $tournamentService->calculatePlayersOfTheDay($this->tournamentId, $referenceDate, 5, true);
        $playersOfTheDayResource = PlayerOfTheDayResource::collection($playersOfTheDay);
        Cache::put("tournament_{$this->tournamentId}_players_of_the_day_limit_5_rel_1_date_{$dateStr}", $playersOfTheDayResource, now()->timezone('UTC')->endOfDay());
    }
}
