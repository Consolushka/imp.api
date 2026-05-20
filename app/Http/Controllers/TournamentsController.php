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

    public function playersOfTheDay(Request $request, int $id)
    {
        $limit = $request->get('limit', 5);
        $useReliability = filter_var($request->get('use_reliability', true), FILTER_VALIDATE_BOOLEAN);

        $cacheKey = "tournament_{$id}_players_of_the_day_limit_{$limit}_rel_{$useReliability}";

        return Cache::remember($cacheKey, now()->timezone('UTC')->endOfDay(), function () use ($id, $limit, $useReliability) {
            $latestGame = Game::where('tournament_id', $id)->orderByDesc('scheduled_at')->first();

            if (!$latestGame) {
                return PlayerOfTheDayResource::collection(collect([]));
            }

            $date = $latestGame->scheduled_at->toDateString();

            $games = Game::where('tournament_id', $id)
                ->whereDate('scheduled_at', $date)
                ->get();

            $gameIds = $games->pluck('id');

            $playerStats = GameTeamPlayerStat::with(['player', 'team'])
                ->whereIn('game_id', $gameIds)
                ->get();

            if ($playerStats->isEmpty()) {
                return PlayerOfTheDayResource::collection(collect([]));
            }

            $statIds = $playerStats->pluck('id')->toArray();
            $imps = $this->impService->calcImpForStatIds($statIds, ['fullGame'], $useReliability);

            $playersOfTheDay = $playerStats->map(function (GameTeamPlayerStat $stat) use ($imps) {
                $imp = $imps[$stat->id]['fullGame']->imp ?? 0;

                return new PlayerOfTheDayDto(
                    id: $stat->player_id,
                    fullName: $stat->player->full_name,
                    teamAlias: $stat->team->alias,
                    playedSeconds: $stat->played_seconds,
                    pts: $stat->points,
                    reb: $stat->rebounds,
                    ast: $stat->assists,
                    imp: $imp
                );
            })->sortByDesc('imp');

            return PlayerOfTheDayResource::collection($playersOfTheDay->take($limit));
        });
    }

    public function summary()
    {
        $tournaments = Tournament::query()
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
