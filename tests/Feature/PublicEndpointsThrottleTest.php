<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicEndpointsThrottleTest extends TestCase
{
    private const LIMIT = 60;

    private const URI = '/api/tournaments/1/weekly-leaders';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tournaments', function ($table) {
            $table->id();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->softDeletes();
        });
        Schema::create('games', function ($table) {
            $table->id();
            $table->integer('tournament_id');
            $table->timestamp('scheduled_at')->nullable();
        });

        DB::table('tournaments')->insert(['id' => 1]);
    }

    private function exhaustLimit(array $headers = []): void
    {
        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->withHeaders($headers)->getJson(self::URI)->assertOk();
        }
    }

    public function test_requests_over_the_limit_are_rejected_with_retry_after(): void
    {
        $this->exhaustLimit();

        $this->getJson(self::URI)
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
    }

    public function test_limit_is_shared_by_all_protected_routes(): void
    {
        $this->exhaustLimit();

        // Без лимита эти запросы ответили бы 200 и 422: счётчик срабатывает раньше контроллера и валидации.
        $this->getJson('/api/tournaments/1/players-of-the-day')->assertTooManyRequests();
        $this->getJson('/api/tournaments/1/daily-insights')->assertTooManyRequests();
        $this->getJson('/api/leaderboard')->assertTooManyRequests();
    }

    public function test_clients_behind_the_proxy_are_counted_separately(): void
    {
        // REMOTE_ADDR в тестах — 127.0.0.1, он играет роль шлюза; клиент — в X-Forwarded-For.
        $this->exhaustLimit(['X-Forwarded-For' => '203.0.113.5']);

        $this->withHeaders(['X-Forwarded-For' => '203.0.113.5'])->getJson(self::URI)->assertTooManyRequests();
        $this->withHeaders(['X-Forwarded-For' => '203.0.113.6'])->getJson(self::URI)->assertOk();
    }
}
