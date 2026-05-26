<?php

namespace App\Providers;

use App\Infrastructure\ImpCalculator\ImpCalculatorConnector;
use App\Service\Narratives\DailyDetectors\AssistMaestroDetector;
use App\Service\Narratives\DailyDetectors\DailyMvpDetector;
use App\Service\Narratives\DailyDetectors\DailyScoringChampDetector;
use App\Service\Narratives\DailyDetectors\EliteEfficiencyDetector;
use App\Service\Narratives\DailyDetectors\GlassEaterDetector;
use App\Service\Narratives\DailyDetectors\HiddenValueDetector;
use App\Service\Narratives\DailyDetectors\HighOctaneDetector;
use App\Service\Narratives\DailyDetectors\NailBiterDetector;
use App\Service\Narratives\DailyDetectors\SteamrollerDetector;
use App\Service\Narratives\DailyDetectors\TheWallDetector;
use App\Service\Narratives\DailyDetectors\TripleDoubleClubDetector;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Narratives\Detectors\CardioSessionDetector;
use App\Service\Narratives\Detectors\CarriedToVictoryDetector;
use App\Service\Narratives\Detectors\DifferenceMakerDetector;
use App\Service\Narratives\Detectors\EmptyStatsDetector;
use App\Service\Narratives\Detectors\ForgottenPillarDetector;
use App\Service\Narratives\Detectors\GlueGuyDetector;
use App\Service\Narratives\Detectors\LoneAtlasDetector;
use App\Service\Narratives\Detectors\SinkholeDetector;
use App\Service\Narratives\Detectors\SparkPlugDetector;
use App\Service\Narratives\Detectors\TripleDoubleDetector;
use App\Service\Narratives\Detectors\UnsungHeroDetector;
use App\Service\Narratives\NarrativeEngine;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        App::bind(ImpCalculatorConnector::class, fn() => new ImpCalculatorConnector(config('services.imp_calculator.url')));

        $this->app->tag([
            DifferenceMakerDetector::class,
            LoneAtlasDetector::class,
            SinkholeDetector::class,
            SparkPlugDetector::class,
            CarriedToVictoryDetector::class,
            EmptyStatsDetector::class,
            ForgottenPillarDetector::class,
            UnsungHeroDetector::class,
            GlueGuyDetector::class,
            CardioSessionDetector::class,
            TripleDoubleDetector::class,
        ], 'narrative.detectors');

        $this->app->tag([
            DailyMvpDetector::class,
            DailyScoringChampDetector::class,
            NailBiterDetector::class,
            SteamrollerDetector::class,
            HighOctaneDetector::class,
            AssistMaestroDetector::class,
            GlassEaterDetector::class,
            TheWallDetector::class,
            TripleDoubleClubDetector::class,
            EliteEfficiencyDetector::class,
            HiddenValueDetector::class,
        ], 'daily_insight.detectors');

        $this->app->bind(NarrativeEngine::class, function ($app) {
            return new NarrativeEngine($app->tagged('narrative.detectors'));
        });

        $this->app->bind(DailyInsightEngine::class, function ($app) {
            return new DailyInsightEngine($app->tagged('daily_insight.detectors'), $app->make(\App\Service\Narratives\NarrativeTemplateService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
