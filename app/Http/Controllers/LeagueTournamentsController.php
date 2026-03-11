<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Models\League;
use App\Models\Tournament;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class LeagueTournamentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(League $league): AnonymousResourceCollection
    {
        return TournamentResource::collection($league->tournaments);
    }

    /**
     * Display the specified resource.
     */
    public function show(League $league, Tournament $tournament): TournamentResource
    {
        return new TournamentResource($tournament);
    }
}
