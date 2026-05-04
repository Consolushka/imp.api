<?php

use App\Http\Controllers\GamesController;
use App\Http\Controllers\GameStatsController;
use App\Http\Controllers\ImpController;
use App\Http\Controllers\ImpRankingsController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\LeagueTournamentsController;
use App\Http\Controllers\PlayersRecentImpController;
use App\Http\Controllers\TournamentGamesController;
use App\Http\Controllers\TournamentsController;
use App\Http\Controllers\TournamentTeamsController;
use App\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::get('summary', [SummaryController::class, 'index']);

Route::get('leagues/summary', [LeaguesController::class, 'summary']);
Route::resource('leagues', LeaguesController::class)->only([
    'index'
]);

Route::resource('leagues.tournaments', LeagueTournamentsController::class)->only([
    'index', 'show'
]);

Route::get('tournaments/summary', [TournamentsController::class, 'summary']);
Route::get('tournaments/{id}/weekly-leaders', [TournamentsController::class, 'weeklyLeaders']);
Route::get('tournaments/{id}/players-of-the-day', [TournamentsController::class, 'playersOfTheDay']);
Route::resource('tournaments', TournamentsController::class)->only([
    'index', 'show'
]);

Route::resource('tournaments.games', TournamentGamesController::class)->only([
    'index'
]);

Route::resource('tournaments.teams', TournamentTeamsController::class)->only([
    'index'
]);

Route::get('games/search', [GamesController::class, 'search']);
Route::resource('games', GamesController::class)->only([
    'index', 'show'
]);

Route::resource('games.stats', GameStatsController::class)->only([
    'index'
]);

Route::post('players/imp/recent', [PlayersRecentImpController::class, 'index']);

Route::resource('imp', ImpController::class)->only([
    'index'
]);

Route::post('imp/calculate-raw', [ImpController::class, 'calculateRaw'])
    ->middleware('static.token');

Route::resource('leaderboard', ImpRankingsController::class)->only([
    'index'
]);
