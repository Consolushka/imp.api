<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ImpCalculateRawTest extends TestCase
{
    public function test_calculate_raw_requires_token(): void
    {
        Config::set('services.imp_calculator.token', 'test-token');

        $response = $this->postJson('/api/imp/calculate-raw', [
            'played_seconds' => 1200,
            'plus_minus' => 10,
            'final_differential' => 5,
            'duration' => 40,
            'pers' => ['fullGame'],
        ]);

        $response->assertStatus(401);
    }

    public function test_calculate_raw_with_valid_token(): void
    {
        Config::set('services.imp_calculator.token', 'test-token');

        $response = $this->postJson('/api/imp/calculate-raw', [
            'played_seconds' => 1200,
            'plus_minus' => 10,
            'final_differential' => 5,
            'duration' => 40,
            'pers' => ['fullGame'],
        ], [
            'X-IMP-TOKEN' => 'test-token'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'fullGame' => ['imp']
                ]
            ]);
    }

    public function test_calculate_raw_validation(): void
    {
        Config::set('services.imp_calculator.token', 'test-token');

        $response = $this->postJson('/api/imp/calculate-raw', [
            'played_seconds' => -1, // invalid
            'plus_minus' => 10,
            'final_differential' => 5,
            'duration' => 30, // invalid, min 40
            'pers' => ['invalid-per'], // invalid
        ], [
            'X-IMP-TOKEN' => 'test-token'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['played_seconds', 'duration', 'pers.0']);
    }
}
