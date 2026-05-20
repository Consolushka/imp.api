<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Http\Requests\TournamentsGamesListRequest;
use App\Models\Game;
use Illuminate\Routing\Controller;

class TournamentGamesController extends Controller
{
    public function index(TournamentsGamesListRequest $request, int $tournamentId)
    {
        return GameResource::collection(
            Game::query()
                ->with(['gameTeamStats', 'gameTeamStats.team'])
                ->where('tournament_id', $tournamentId)
                ->orderBy('scheduled_at', 'desc')
                ->paginate(
                    $request->getPerPage(),
                    ['*'],
                    'page',
                    $request->getPage()
                )
        );
    }
}
