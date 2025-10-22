<?php

namespace App\Http\Controllers;

use App\Dtos\GameTeamPlayerStatsDto;
use App\Http\Requests\PlayerStatImpRequest;
use App\Http\Resources\ImpResource;
use App\Models\GameTeamPlayerStat;
use App\Service\Imp\Dtos\ImpDto;
use App\Service\Imp\Dtos\ImpPerDto;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\PersEnum;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ImpController extends Controller
{
    public function index(PlayerStatImpRequest $request)
    {
        $imps = [];
        $ids = $request->getIds();
        $records = DB::table('game_team_player_stats')
            ->select(
                'game_team_player_stats.id',
                'plus_minus',
                'played_seconds',
                'final_differential',
                'regulation_duration'
            )
            ->leftJoin('game_team_stats', function ($join) {
                $join->on('game_team_stats.game_id', '=', 'game_team_player_stats.game_id')
                    ->on('game_team_stats.team_id', '=', 'game_team_player_stats.team_id');
            })
            ->leftJoin('games', 'games.id', '=', 'game_team_stats.game_id')
            ->leftJoin('tournaments', 'tournaments.id', '=', 'games.tournament_id')
            ->whereIn('game_team_player_stats.id', $ids)
            ->get()
            ->toArray();

        $stats = array_map(fn($item) => GameTeamPlayerStatsDto::fromArray((array)$item), $records);

        foreach ($stats as $stat) {
            /**@var GameTeamPlayerStatsDto $stat */
            $impPers = [];
            foreach ($request->pers as $per) {
                $impPers[] = new ImpPerDto(PersEnum::from($per), ImpCalculator::evaluatePer($stat->played_seconds, $stat->plus_minus, $stat->final_differential, $stat->regulation_duration, PersEnum::from($per)));
            }
            $imps[] = new ImpDto($stat->id, $impPers);
        }

        return [
            'data' => ImpResource::collection($imps),
        ];
    }
}
