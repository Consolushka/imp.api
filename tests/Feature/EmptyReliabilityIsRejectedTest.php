<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmptyReliabilityIsRejectedTest extends TestCase
{
    public static function endpoints(): array
    {
        return [
            'leaderboard' => ['GET', '/api/leaderboard?use_reliability=', []],
            'imp' => ['GET', '/api/imp?use_reliability=', []],
            'game' => ['GET', '/api/games/1?use_reliability=', []],
            'recent' => ['POST', '/api/players/imp/recent', ['use_reliability' => '']],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_empty_value_does_not_become_false(string $method, string $uri, array $payload): void
    {
        $this->json($method, $uri, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('use_reliability');
    }
}
