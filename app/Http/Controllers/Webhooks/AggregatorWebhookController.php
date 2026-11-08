<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Requests\AggregatorWebhookRequest;
use App\Jobs\WarmTournamentCacheJob;
use Illuminate\Routing\Controller;

class AggregatorWebhookController extends Controller
{
    public function gameImported(AggregatorWebhookRequest $request)
    {
        $tournamentId = $request->validated('tournament_id');

        // Задержка даёт Argus досохранить остальные игры опроса, чтобы прогрев прошёл один раз на все.
        // ponytail: окно подобрано на глаз; если опрос дольше пяти минут, прогревов будет несколько.
        WarmTournamentCacheJob::dispatch((int) $tournamentId)->delay(now()->addMinutes(5));

        return response()->json(['status' => 'Accepted'], 202);
    }
}
