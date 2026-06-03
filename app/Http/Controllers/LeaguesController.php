<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeagueSummaryResource;
use App\Models\League;
use Illuminate\Routing\Controller;

class LeaguesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return [
            'data' => League::orderBy('order')->get()
        ];
    }

    public function summary()
    {
        $leagues = League::withCount(['tournaments', 'games'])
            ->orderBy('order')
            ->get();

        return LeagueSummaryResource::collection($leagues);
    }
}
