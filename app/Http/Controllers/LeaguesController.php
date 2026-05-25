<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeagueResource;
use App\Http\Resources\LeagueSummaryResource;
use App\Models\League;
use Illuminate\Routing\Controller;

class LeaguesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<LeagueResource>
     */
    public function index()
    {
        return LeagueResource::collection(League::orderByDesc('tier')->get());
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<LeagueSummaryResource>
     */
    public function summary()
    {
        $leagues = League::withCount(['tournaments', 'games'])
            ->orderByDesc('tier')
            ->get();

        return LeagueSummaryResource::collection($leagues);
    }
}
