<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\Game;
use App\Models\GameTeamStat;
use App\Models\Team;
use Illuminate\Routing\Controller;

class TournamentTeamsController extends Controller
{
    /**
     * @param int $tournamentId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<TeamResource>
     */
    public function index(int $tournamentId)
    {
        $gameIds = Game::query()
            ->where('tournament_id', $tournamentId)
            ->get('id');
        $teamIds = GameTeamStat::query()
            ->whereIn('game_id', $gameIds)
            ->select('team_id')
            ->distinct()
            ->get('team_id');

        return TeamResource::collection(
            Team::query()
                ->whereIn('id', $teamIds)
                ->get()
        );
    }
}
