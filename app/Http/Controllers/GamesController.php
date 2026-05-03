<?php

namespace App\Http\Controllers;

use App\Http\Requests\GamesListRequest;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GamesController extends Controller
{
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

        return $builder->paginate(
            $request->getPerPage(),
            ['*'],
            'page',
            $request->getPage()
        );
    }

    public function search(Request $request)
    {
        $text = $request->get('text');
        $limit = $request->get('limit', 10);

        if (empty($text)) {
            return ['data' => []];
        }

        $games = Game::query()
            ->where(function ($query) use ($text) {
                $query->where('title', 'ilike', "%{$text}%")
                    ->orWhereHas('gameTeamStats.team', function ($query) use ($text) {
                        $query->where('name', 'ilike', "%{$text}%")
                            ->orWhere('alias', 'ilike', "%{$text}%");
                    });
            })
            ->limit($limit)
            ->get();

        return [
            'data' => $games
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $game = Game::query()
            ->with(['gameTeamStats', 'gameTeamStats.team'])
            ->where('id', $id)
            ->first();

        /**@var GameTeamPlayerStat[] $playersStatsInGame*/
        $playersStatsInGame = GameTeamPlayerStat::query()
            ->with('player')
            ->where('game_id', $id)
            ->get()
            ->groupBy('team_id');

        $game->gameTeamStats->each(function ($gameTeamStat) use ($playersStatsInGame) {
            $gameTeamStat->playerStats = $playersStatsInGame[$gameTeamStat->team_id];
        });

        return [
            'data' => $game
        ];
    }
}
