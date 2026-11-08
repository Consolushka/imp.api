<?php

namespace App\Jobs;

use App\Http\Resources\WeeklyLeaderResource;
use App\Models\Tournament;
use App\Service\Narratives\DailyInsightEngine;
use App\Service\Tournament\TournamentCacheKeys;
use App\Service\Tournament\TournamentService;
use App\Service\Tournament\WeeklyLeadersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

// Argus присылает событие на каждую игру опроса, а прогрев считает весь турнир. Пока job турнира ждёт запуска,
// новые события по нему отбрасываются: она и так посчитает свежие данные. Событие во время работы ставит
// следующую job, чтобы игры, сохранённые после её старта, не пропали.
class WarmTournamentCacheJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Если job потеряется и не запустится, события по турниру отбрасываются только это время.
    // Должен быть больше задержки запуска в webhook, иначе дубли будут проходить, пока job ждёт.
    public int $uniqueFor = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $tournamentId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->tournamentId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        WeeklyLeadersService $weeklyLeadersService,
        TournamentService $tournamentService,
        DailyInsightEngine $dailyInsightEngine
    ): void {
        $tournament = Tournament::find($this->tournamentId);
        if (!$tournament) {
            return;
        }

        // Рейтинг не греем: наборов фильтров слишком много. С новой версией первый запрос посчитает его заново.
        // Первым шагом, чтобы падение тяжёлых шагов ниже не оставило устаревший рейтинг.
        // ponytail: записи старых версий остаются в таблице кеша до планового удаления просроченных (API-10).
        // Срок обязан быть больше суток, которые живёт сам рейтинг: иначе, когда версия истечёт, снова прочитается старый расчёт.
        // Бессрочно нельзя: кеш пишет такую запись на 10 лет вперёд, и с 2028 года дата не поместится в колонку.
        Cache::put(TournamentCacheKeys::leaderboardVersion($this->tournamentId), now()->getTimestampMs(), now()->addDays(2));

        // 1. Warm Best Player (24h)
        $bestPlayer = $tournamentService->getBestPlayerFullName($tournament);
        Cache::put(TournamentCacheKeys::bestPlayer($this->tournamentId), $bestPlayer, now()->addDay());

        // 2. Warm Weekly Leaders (End of day UTC)
        $referenceDate = now();
        $dateStr = $referenceDate->toDateString();
        $leaders = $weeklyLeadersService->calculateLeaders($this->tournamentId, $referenceDate);
        $leadersResource = WeeklyLeaderResource::collection($leaders);
        Cache::put(TournamentCacheKeys::weeklyLeaders($this->tournamentId, $dateStr), $leadersResource, now()->timezone('UTC')->endOfDay());

        // 3–4. Игроки дня и инсайты — за последний игровой день, иначе греется не тот день, который отдаст эндпоинт.
        // В кэш кладутся данные (DTO), ресурс из них собирает контроллер.
        $gameDay = $tournamentService->lastGameDay($this->tournamentId);
        $gameDayStr = $gameDay->toDateString();

        $playersOfTheDay = $tournamentService->calculatePlayersOfTheDay($this->tournamentId, $gameDay, 5, true);
        Cache::put(TournamentCacheKeys::playersOfTheDay($this->tournamentId, 5, true, $gameDayStr), $playersOfTheDay, now()->timezone('UTC')->endOfDay());

        $insights = $dailyInsightEngine->analyze($tournamentService->getDailyContext($this->tournamentId, $gameDay));
        Cache::put(TournamentCacheKeys::dailyInsights($this->tournamentId, $gameDayStr), $insights, now()->timezone('UTC')->endOfDay());
    }
}
