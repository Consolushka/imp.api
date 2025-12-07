<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameTeamStat;
use App\Models\Team;
use Illuminate\Routing\Controller;

class TournamentTeamsController extends Controller
{
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

        return [
            'data' => Team::query()
                ->whereIn('id', $teamIds)
                ->get()
        ];
    }
}
