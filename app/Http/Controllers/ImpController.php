<?php

namespace App\Http\Controllers;

use App\Dtos\GameTeamPlayerStatsDto;
use App\Http\Requests\ImpCalculateRawRequest;
use App\Http\Requests\PlayerStatImpRequest;
use App\Http\Resources\ImpResource;
use App\Models\GameTeamPlayerStat;
use App\Service\Imp\Dtos\ImpDto;
use App\Service\Imp\Dtos\ImpPerDto;
use App\Service\Imp\ImpCalculator;
use App\Service\Imp\ImpService;
use App\Service\Imp\PersEnum;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ImpController extends Controller
{
    public function index(PlayerStatImpRequest $request, ImpService $impService)
    {
        $ids = $request->getIds();
        $imps = $impService->calcImpForStatIds($ids, $request->pers, $request->useReliability());

        return [
            'data' => $imps,
        ];
    }

    public function calculateRaw(ImpCalculateRawRequest $request)
    {
        $pers = $request->getPers();
        $useReliability = $request->useReliability();

        $result = [];
        foreach ($pers as $per) {
            $result[$per] = new ImpPerDto(ImpCalculator::evaluatePer(
                $request->getPlayedSeconds(),
                $request->getPlusMinus(),
                $request->getFinalDifferential(),
                $request->getDuration(),
                PersEnum::from($per),
                $useReliability
            ));
        }

        return [
            'data' => $result,
        ];
    }

    /**
     * @param mixed $ids
     * @param array $pers
     * @param bool $useReliability
     * @return array<int, array<string, ImpPerDto>>
     */
    public function calcImpForStatIds(array $ids, array $pers, bool $useReliability = true): array
    {
        return (new ImpService())->calcImpForStatIds($ids, $pers, $useReliability);
    }
}
