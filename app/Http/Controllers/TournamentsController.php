<?php

namespace App\Http\Controllers;

use App\Dtos\PlayerOfTheDayDto;
use App\Http\Resources\PlayerOfTheDayResource;
use App\Http\Resources\TournamentResource;
use App\Http\Resources\TournamentSummaryResource;
use App\Http\Resources\WeeklyLeaderResource;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\GameTeamStat;
use App\Models\Player;
use App\Models\Tournament;
use App\Service\Imp\ImpService;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WeeklyLeadersService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class TournamentsController extends Controller
{
    public function __construct(
        private readonly ImpService $impService,
        private readonly TournamentService $tournamentService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return TournamentResource::collection(Tournament::all());
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<PlayerOfTheDayResource>
     */
    public function playersOfTheDay(Request $request, int $id)
    {
        $limit = (int)$request->get('limit', 5);
        $useReliability = filter_var($request->get('use_reliability', true), FILTER_VALIDATE_BOOLEAN);
        $relInt = $useReliability ? 1 : 0;
        $date = $request->has('date') ? Carbon::parse($request->get('date')) : now();
        $dateStr = $date->toDateString();

        $cacheKey = "tournament_{$id}_players_of_the_day_limit_{$limit}_rel_{$relInt}_date_{$dateStr}";

        return Cache::remember($cacheKey, now()->timezone('UTC')->endOfDay(), function () use ($id, $limit, $useReliability, $date) {
            $playersOfTheDay = $this->tournamentService->calculatePlayersOfTheDay($id, $date, $limit, $useReliability);

            return PlayerOfTheDayResource::collection($playersOfTheDay);
        });
    }

    public function dailyInsights(Request $request, int $id, DailyInsightEngine $engine)
    {
        $date = $request->has('date') ? Carbon::parse($request->get('date')) : now();
        $dateStr = $date->toDateString();

        $cacheKey = "tournament_{$id}_daily_insights_{$dateStr}";

        return Cache::remember($cacheKey, now()->endOfDay(), function () use ($id, $date, $engine) {
            $context = $this->tournamentService->getDailyContext($id, $date);

            return $engine->analyze($context);
        });
    }

    public function summary(Request $request)
    {
        $tournaments = Tournament::query()
            ->when($request->get('league_id'), function ($query, $leagueId) {
                $query->where('league_id', $leagueId);
            })
            ->with(['latestPollLog'])
            ->withCount('games')
            ->addSelect(['teams_count' => GameTeamStat::query()
                ->selectRaw('count(distinct team_id)')
                ->whereIn('game_id', function ($query) {
                    $query->select('id')
                        ->from('games')
                        ->whereColumn('tournament_id', 'tournaments.id');
                })
            ])
            ->get();

        foreach ($tournaments as $tournament) {
            $tournament->best_player_full_name = Cache::remember(
                "tournament_{$tournament->id}_best_player",
                now()->addDay(),
                fn() => $this->tournamentService->getBestPlayerFullName($tournament)
            );

            $tournament->next_update_at = $tournament->latestPollLog 
                ? $tournament->latestPollLog->poll_start_at->addMinutes(30)
                : null;
        }

        return TournamentSummaryResource::collection($tournaments);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament): TournamentResource
    {
        return new TournamentResource($tournament);
    }

    public function weeklyLeaders(Request $request, int $id, WeeklyLeadersService $weeklyLeadersService): AnonymousResourceCollection
    {
        $referenceDate = $request->has('date') ? Carbon::parse($request->get('date')) : now();
        $dateStr = $referenceDate->toDateString();

        $cacheKey = "tournament_{$id}_weekly_leaders_{$dateStr}";

        return Cache::remember($cacheKey, now()->timezone('UTC')->endOfDay(), function () use ($id, $referenceDate, $weeklyLeadersService) {
            $leaders = $weeklyLeadersService->calculateLeaders($id, $referenceDate);

            return WeeklyLeaderResource::collection($leaders);
        });
    }
}
