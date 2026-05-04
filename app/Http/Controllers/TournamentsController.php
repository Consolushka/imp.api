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
use App\Service\Tournament\WeeklyLeadersService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class TournamentsController extends Controller
{
    public function __construct(
        private readonly ImpService $impService
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
                return [
                    'data' => []
                ];
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
                return [
                    'data' => []
                ];
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
            $tournament->best_player_full_name = $this->getBestPlayerFullName($tournament);
        }

        return TournamentSummaryResource::collection($tournaments);
    }

    private function getBestPlayerFullName(Tournament $tournament): string
    {
        $gameIds = $tournament->games()->pluck('id');
        if ($gameIds->isEmpty() || !$tournament->regulation_duration) {
            return 'N/A';
        }

        $minPlayedSeconds = $tournament->regulation_duration * 60 * 0.4;

        $teamGamesCount = GameTeamStat::query()
            ->whereIn('game_id', $gameIds)
            ->groupBy('team_id')
            ->selectRaw('team_id, count(*) as count')
            ->pluck('count', 'team_id');

        $playerStats = GameTeamPlayerStat::query()
            ->whereIn('game_id', $gameIds)
            ->where('played_seconds', '>=', $minPlayedSeconds)
            ->get(['id', 'player_id', 'team_id']);

        $playerGroups = $playerStats->groupBy(function ($item) {
            return $item->player_id . '_' . $item->team_id;
        });

        $validGroups = [];
        foreach ($playerGroups as $key => $stats) {
            [$playerId, $teamId] = explode('_', $key);
            $gamesPlayed = $stats->count();
            $totalTeamGames = $teamGamesCount[$teamId] ?? 0;

            if ($totalTeamGames > 0 && $gamesPlayed >= ceil($totalTeamGames * 0.3)) {
                $validGroups[$key] = [
                    'player_id' => (int)$playerId,
                    'stat_ids' => $stats->pluck('id')->toArray(),
                ];
            }
        }

        if (empty($validGroups)) {
            return 'N/A';
        }

        $allStatIds = [];
        foreach ($validGroups as $group) {
            $allStatIds = array_merge($allStatIds, $group['stat_ids']);
        }

        $imps = $this->impService->calcImpForStatIds($allStatIds, ['fullGame']);

        $bestPlayerId = null;
        $maxAvgImp = -INF;

        foreach ($validGroups as $key => $group) {
            $sumImp = 0;
            foreach ($group['stat_ids'] as $statId) {
                $sumImp += $imps[$statId]['fullGame']->imp ?? 0;
            }
            $avgImp = $sumImp / count($group['stat_ids']);

            if ($avgImp > $maxAvgImp) {
                $maxAvgImp = $avgImp;
                $bestPlayerId = $group['player_id'];
            }
        }

        if ($bestPlayerId) {
            $player = Player::find($bestPlayerId);
            return $player ? $player->full_name : 'N/A';
        }

        return 'N/A';
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament): TournamentResource
    {
        return new TournamentResource($tournament);
    }

    public function weeklyLeaders(Request $request, WeeklyLeadersService $weeklyLeadersService): AnonymousResourceCollection
    {
        $tournamentIds = $request->get('tournamentIds');
        if (is_string($tournamentIds)) {
            $tournamentIds = array_filter(explode(',', $tournamentIds));
        }

        $referenceDate = $request->has('date') ? Carbon::parse($request->get('date')) : null;

        $leaders = $weeklyLeadersService->calculateLeaders((array)$tournamentIds, $referenceDate);

        return WeeklyLeaderResource::collection($leaders);
    }
}
