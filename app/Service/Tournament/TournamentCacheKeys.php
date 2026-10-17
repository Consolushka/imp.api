<?php

namespace App\Service\Tournament;

// Ключи кеша в одном месте: контроллеры читают, джоба прогрева пишет — расхождение сделало бы прогрев бесполезным.
final class TournamentCacheKeys
{
    public static function bestPlayer(int $tournamentId): string
    {
        return "tournament_{$tournamentId}_best_player";
    }

    public static function weeklyLeaders(int $tournamentId, string $date): string
    {
        return "tournament_{$tournamentId}_weekly_leaders_{$date}";
    }

    public static function playersOfTheDay(int $tournamentId, int $limit, bool $useReliability, string $date): string
    {
        $rel = $useReliability ? 1 : 0;

        return "tournament_{$tournamentId}_players_of_the_day_limit_{$limit}_rel_{$rel}_date_{$date}";
    }
}
