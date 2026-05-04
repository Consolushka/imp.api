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

        WarmTournamentCacheJob::dispatch((int)$tournamentId);

        return response()->json(['status' => 'Accepted'], 202);
    }
}
