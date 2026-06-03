<?php

namespace App\Http\Controllers;

use App\Http\Resources\SummaryResource;
use App\Models\Game;
use App\Models\GameTeamPlayerStat;
use App\Models\League;
use App\Models\Player;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class SummaryController extends Controller
{
    /**
     * @return SummaryResource
     */
    public function index()
    {
        $data = Cache::remember('summary_stats', now()->addWeek(), function () {
            return [
                'totalDataPoints' => $this->formatLargeNumber(GameTeamPlayerStat::count(), true),
                'activeLeagues'   => League::count(),
                'trackedPlayers'  => number_format(Player::count()),
                'totalMatches'    => number_format(Game::count()),
            ];
        });

        return new SummaryResource($data);
    }

    private function formatLargeNumber(int $number, bool $plus = false): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M' . ($plus ? '+' : '');
        }

        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K' . ($plus ? '+' : '');
        }

        return (string)$number;
    }
}
