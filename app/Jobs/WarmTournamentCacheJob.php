<?php

namespace App\Jobs;

use App\Http\Resources\WeeklyLeaderResource;
use App\Models\Tournament;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Tournament\TournamentCacheKeys;
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
        TournamentService $tournamentService,
        DailyInsightEngine $dailyInsightEngine
    ): void {
        $tournament = Tournament::find($this->tournamentId);
        if (!$tournament) {
            return;
        }

        // 1. Warm Best Player (24h)
        $bestPlayer = $tournamentService->getBestPlayerFullName($tournament);
        Cache::put(TournamentCacheKeys::bestPlayer($this->tournamentId), $bestPlayer, now()->addDay());

        // 2. Warm Weekly Leaders (End of day UTC)
        $referenceDate = now();
        $dateStr = $referenceDate->toDateString();
        $leaders = $weeklyLeadersService->calculateLeaders($this->tournamentId, $referenceDate);
        $leadersResource = WeeklyLeaderResource::collection($leaders);
        Cache::put(TournamentCacheKeys::weeklyLeaders($this->tournamentId, $dateStr), $leadersResource, now()->timezone('UTC')->endOfDay());

        // 3–4. Игроки дня и инсайты — за последний игровой день, иначе греется не тот день, который отдаст эндпоинт.
        // В кэш кладутся данные (DTO), ресурс из них собирает контроллер.
        $gameDay = $tournamentService->lastGameDay($this->tournamentId);
        $gameDayStr = $gameDay->toDateString();

        $playersOfTheDay = $tournamentService->calculatePlayersOfTheDay($this->tournamentId, $gameDay, 5, true);
        Cache::put(TournamentCacheKeys::playersOfTheDay($this->tournamentId, 5, true, $gameDayStr), $playersOfTheDay, now()->timezone('UTC')->endOfDay());

        $insights = $dailyInsightEngine->analyze($tournamentService->getDailyContext($this->tournamentId, $gameDay));
        Cache::put(TournamentCacheKeys::dailyInsights($this->tournamentId, $gameDayStr), $insights, now()->timezone('UTC')->endOfDay());
    }
}
