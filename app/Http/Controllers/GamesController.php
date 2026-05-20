<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Http\Resources\PlayerKeyPerformanceResource;
use App\Http\Requests\GamesListRequest;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use App\Dtos\PlayerNarrativeContext;
use App\Models\GameTeamStat;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\PersEnum;
use App\Service\Narratives\NarrativeEngine;
use App\Service\Narratives\NarrativeTemplateService;
use Illuminate\Support\Facades\Cache;

class GamesController extends Controller
{
    /**
     * @param int $id
     * @param NarrativeEngine $engine
     * @param NarrativeTemplateService $templateService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<PlayerKeyPerformanceResource>
     */
    public function keyPerformances(
        int $id,
        NarrativeEngine $engine,
        NarrativeTemplateService $templateService
    ) {
        return Cache::remember("game_{$id}_key_performances", now()->addWeek(), function () use ($id, $engine, $templateService) {
            $game = Game::query()
                ->with(['gameTeamStats', 'gameTeamPlayerStats.player'])
                ->findOrFail($id);

            $gamePlayedMinutes = $game->duration ?: 40;
            $allPlayerStats = $game->gameTeamPlayerStats;
            
            // 1. Calculate IMP for everyone to get contextual baseline
            $calculatedStats = $allPlayerStats->map(function (GameTeamPlayerStat $stat) use ($game, $gamePlayedMinutes) {
                /** @var GameTeamStat $teamStat */
                $teamStat = $game->gameTeamStats->firstWhere('team_id', $stat->team_id);
                $finalDiff = $teamStat ? $teamStat->final_differential : 0;
                $teamWon = $finalDiff > 0;

                $impPerStart = ImpCalculator::evaluatePer(
                    $stat->played_seconds,
                    $stat->plus_minus,
                    $finalDiff,
                    $gamePlayedMinutes,
                    PersEnum::Start
                );

                return [
                    'stat' => $stat,
                    'impPerStart' => $impPerStart,
                    'teamWon' => $teamWon,
                ];
            });

            $teamAverages = $calculatedStats->groupBy(fn($item) => $item['stat']->team_id)
                ->map(fn($group) => $group->avg('impPerStart'));

            $maxImp = $calculatedStats->max('impPerStart');
            $maxPoints = $allPlayerStats->max('points');
            $maxRebounds = $allPlayerStats->max('rebounds');
            $maxAssists = $allPlayerStats->max('assists');
            $gameDurationSeconds = $gamePlayedMinutes * 60;

            // 2. Build Contexts and Run Engine
            $results = [];
            foreach ($calculatedStats as $data) {
                /** @var GameTeamPlayerStat $stat */
                $stat = $data['stat'];
                
                $context = new PlayerNarrativeContext(
                    playerId: $stat->player_id,
                    points: $stat->points,
                    rebounds: $stat->rebounds,
                    assists: $stat->assists,
                    steals: $stat->steals,
                    blocks: $stat->blocks,
                    playedSeconds: $stat->played_seconds,
                    plusMinus: $stat->plus_minus,
                    impPerStart: $data['impPerStart'],
                    teamWon: $data['teamWon'],
                    teamAverageGameImpPerStart: $teamAverages[$stat->team_id],
                    maxGameImp: $maxImp,
                    maxGamePoints: $maxPoints,
                    maxGameRebounds: $maxRebounds,
                    maxGameAssists: $maxAssists,
                    gameDurationSeconds: $gameDurationSeconds
                );

                $slugs = $engine->analyze($context);
                $narratives = [];
                
                foreach ($slugs as $slug) {
                    $text = $templateService->enrich($slug, $context);
                    if ($text) {
                        $narratives[] = [
                            'slug' => $slug,
                            'text' => $text,
                        ];
                    }
                }

                if (!empty($narratives)) {
                    $results[] = [
                        'player_id' => $stat->player_id,
                        'player_name' => $stat->player->name,
                        'team_id' => $stat->team_id,
                        'narratives' => $narratives,
                    ];
                }
            }

            return PlayerKeyPerformanceResource::collection($results);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(GamesListRequest $request)
    {
        $builder = Game::query()
            ->with(['gameTeamStats', 'gameTeamStats.team'])
            ->orderBy('scheduled_at', 'desc');

        if ($request->getDate()) {
            $builder->whereDate('scheduled_at', $request->getDate());
        }

        return GameResource::collection(
            $builder->paginate(
                $request->getPerPage(),
                ['*'],
                'page',
                $request->getPage()
            )
        );
    }

    public function search(Request $request)
    {
        $text = $request->get('text');
        $limit = $request->get('limit', 10);

        if (empty($text)) {
            return GameResource::collection(collect([]));
        }

        $games = Game::query()
            ->with(['gameTeamStats', 'gameTeamStats.team'])
            ->where(function ($query) use ($text) {
                $query->where('title', 'ilike', "%{$text}%")
                    ->orWhereHas('gameTeamStats.team', function ($query) use ($text) {
                        $query->where('name', 'ilike', "%{$text}%")
                            ->orWhere('alias', 'ilike', "%{$text}%");
                    });
            })
            ->limit($limit)
            ->get();

        return GameResource::collection($games);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $game = Game::query()
            ->with(['gameTeamStats', 'gameTeamStats.team'])
            ->where('id', $id)
            ->firstOrFail();

        /**@var GameTeamPlayerStat[] $playersStatsInGame*/
        $playersStatsInGame = GameTeamPlayerStat::query()
            ->with('player')
            ->where('game_id', $id)
            ->get()
            ->groupBy('team_id');

        $game->gameTeamStats->each(function ($gameTeamStat) use ($playersStatsInGame) {
            $gameTeamStat->playerStats = $playersStatsInGame[$gameTeamStat->team_id] ?? collect([]);
        });

        return new GameResource($game);
    }
}
