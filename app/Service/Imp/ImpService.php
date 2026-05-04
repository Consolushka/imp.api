<?php

namespace App\Service\Imp;

use App\Dtos\GameTeamPlayerStatsDto;
use App\Service\Imp\Dtos\ImpPerDto;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\PersEnum;
use Illuminate\Support\Facades\DB;

class ImpService
{
    /**
     * @param array<int> $ids
     * @param array<string> $pers
     * @param bool $useReliability
     * @return array<int, array<string, ImpPerDto>>
     */
    public function calcImpForStatIds(array $ids, array $pers, bool $useReliability = true): array
    {
        $imps = [];
        $records = DB::table('game_team_player_stats')
            ->select(
                'game_team_player_stats.id',
                'plus_minus',
                'played_seconds',
                'final_differential',
                'games.duration'
            )
            ->where('game_team_player_stats.played_seconds', '>', 0)
            ->leftJoin('game_team_stats', function ($join) {
                $join->on('game_team_stats.game_id', '=', 'game_team_player_stats.game_id')
                    ->on('game_team_stats.team_id', '=', 'game_team_player_stats.team_id');
            })
            ->leftJoin('games', 'games.id', '=', 'game_team_stats.game_id')
            ->whereIn('game_team_player_stats.id', $ids)
            ->get()
            ->toArray();

        $stats = array_map(fn($item) => GameTeamPlayerStatsDto::fromArray((array)$item), $records);

        foreach ($stats as $stat) {
            /** @var GameTeamPlayerStatsDto $stat */
            $impPers = [];
            foreach ($pers as $per) {
                $impPers[$per] = new ImpPerDto(ImpCalculator::evaluatePer(
                    (int)$stat->played_seconds,
                    (int)$stat->plus_minus,
                    (int)$stat->final_differential,
                    (int)$stat->duration,
                    PersEnum::from($per),
                    $useReliability
                ));
            }
            $imps[intval($stat->id)] = $impPers;
        }
        return $imps;
    }
}
