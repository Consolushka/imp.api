<?php

use App\Http\Controllers\GamesController;
use App\Http\Controllers\GameStatsController;
use App\Http\Controllers\ImpController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\LeagueTournamentsController;
use App\Http\Controllers\TournamentGamesController;
use App\Http\Controllers\TournamentsController;
use App\Http\Controllers\TournamentTeamsController;
use Illuminate\Support\Facades\Route;

Route::resource('leagues', LeaguesController::class)->only([
    'index'
]);

Route::resource('leagues.tournaments', LeagueTournamentsController::class)->only([
    'index', 'show'
]);

Route::resource('tournaments', TournamentsController::class)->only([
    'index'
]);

Route::resource('tournaments.games', TournamentGamesController::class)->only([
    'index'
]);

Route::resource('tournaments.teams', TournamentTeamsController::class)->only([
    'index'
]);

Route::resource('games', GamesController::class)->only([
    'index', 'show'
]);

Route::resource('games.stats', GameStatsController::class)->only([
    'index'
]);

Route::resource('imp', ImpController::class)->only([
    'index'
]);

