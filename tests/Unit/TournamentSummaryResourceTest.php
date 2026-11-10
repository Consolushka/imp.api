<?php

namespace Tests\Unit;

use App\Http\Resources\TournamentSummaryResource;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TournamentSummaryResourceTest extends TestCase
{
    private function nextUpdateAt(?Carbon $nextUpdateAt): ?string
    {
        $tournament = new Tournament(['name' => 'Cup']);
        $tournament->next_update_at = $nextUpdateAt;

        return (new TournamentSummaryResource($tournament))->toArray(new Request)['next_update_at'];
    }

    public function test_next_update_at_is_null_when_no_poll_is_promised(): void
    {
        $this->assertNull($this->nextUpdateAt(null));
    }

    public function test_next_update_at_is_formatted_when_poll_is_promised(): void
    {
        $this->assertSame('2026-11-10 14:30:00', $this->nextUpdateAt(Carbon::parse('2026-11-10 14:30:00')));
    }
}
