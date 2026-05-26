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
    public function insights(
        int $id,
        NarrativeEngine $engine,
        NarrativeTemplateService $templateService
    ) {
        return Cache::remember("game_{$id}_insights", now()->addWeek(), function () use ($id, $engine, $templateService) {
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
                    PersEnum::FullGame
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

            // 2. Build Contexts and Gather potential narratives
            $allOptions = [];

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
                    teamAverageGameImpPerStart: $teamAverages[$stat->team_id] ?? 0,
                    maxGameImp: $maxImp,
                    maxGamePoints: $maxPoints,
                    maxGameRebounds: $maxRebounds,
                    maxGameAssists: $maxAssists,
                    gameDurationSeconds: $gameDurationSeconds
                );

                foreach ($engine->getDetectedDetectors($context) as $detector) {
                    $allOptions[] = [
                        'player_id' => $stat->player_id,
                        'player_full_name' => $stat->player->full_name,
                        'team_id' => $stat->team_id,
                        'slug' => $detector->getSlug(),
                        'tier' => $detector->getTier(),
                        'weight' => $detector->getWeight($context),
                        'context' => $context,
                    ];
                }
            }

            // 3. Sort options by Tier (lower is better) then Weight (higher is better)
            usort($allOptions, function ($a, $b) {
                if ($a['tier'] !== $b['tier']) {
                    return $a['tier'] <=> $b['tier'];
                }
                return $b['weight'] <=> $a['weight'];
            });

            // 4. Assign narratives (max 1 per player, unique across game)
            $assignedPlayers = [];
            $assignedSlugs = [];
            $results = [];

            foreach ($allOptions as $option) {
                if (isset($assignedPlayers[$option['player_id']])) {
                    continue;
                }
                if (isset($assignedSlugs[$option['slug']])) {
                    continue;
                }

                $text = $templateService->enrich($option['slug'], $option['context']);
                if ($text) {
                    $assignedPlayers[$option['player_id']] = true;
                    $assignedSlugs[$option['slug']] = true;
                    $results[] = [
                        'player_id' => $option['player_id'],
                        'player_full_name' => $option['player_full_name'],
                        'team_id' => $option['team_id'],
                        'narrative' => [
                            'slug' => $option['slug'],
                            'text' => $text,
                            'value' => $this->formatNarrativeValue($option['slug'], $option['context']),
                        ],
                    ];
                }
            }

            return PlayerKeyPerformanceResource::collection($results);
        });
    }

    private function formatNarrativeValue(string $slug, PlayerNarrativeContext $context): string
    {
        return match ($slug) {
            'lone_atlas', 'difference_maker', 'glue_guy', 'sinkhole', 'spark_plug', 'unsung_hero' => number_format($context->impPerStart, 1) . ' IMP',
            'empty_stats', 'triple_double' => $context->points . ' PTS',
            'carried_to_victory', 'cardio_session' => round($context->playedSeconds / 60) . ' MIN',
            default => number_format($context->impPerStart, 1) . ' IMP',
        };
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
            ->orderBy('scheduled_at', 'desc')
            ->limit($limit)
            ->get();

        return GameResource::collection($games);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        $game = Game::query()
            ->with(['gameTeamStats', 'gameTeamStats.team'])
            ->where('id', $id)
            ->firstOrFail();

        $per = PersEnum::tryFrom($request->get('per')) ?: PersEnum::FullGame;
        $useReliability = filter_var($request->get('reliability', true), FILTER_VALIDATE_BOOLEAN);
        $gameDuration = $game->duration ?: 40;

        /**@var GameTeamPlayerStat[] $playersStatsInGame*/
        $playersStatsInGame = GameTeamPlayerStat::query()
            ->with('player')
            ->where('game_id', $id)
            ->get()
            ->each(function (GameTeamPlayerStat $stat) use ($game, $gameDuration, $per, $useReliability) {
                /** @var GameTeamStat $teamStat */
                $teamStat = $game->gameTeamStats->firstWhere('team_id', $stat->team_id);
                $finalDiff = $teamStat ? $teamStat->final_differential : 0;

                $stat->imp = ImpCalculator::evaluatePer(
                    $stat->played_seconds,
                    $stat->plus_minus,
                    $finalDiff,
                    $gameDuration,
                    $per,
                    $useReliability
                );
            })
            ->groupBy('team_id');

        $game->gameTeamStats->each(function ($gameTeamStat) use ($playersStatsInGame) {
            $gameTeamStat->playerStats = $playersStatsInGame[$gameTeamStat->team_id] ?? collect([]);
        });

        return new GameResource($game);
    }
}
