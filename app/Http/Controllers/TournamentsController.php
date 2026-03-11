<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TournamentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return TournamentResource::collection(Tournament::all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament): TournamentResource
    {
        return new TournamentResource($tournament);
    }
}
