<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InvalidRouteIdsReturnNotFoundTest extends TestCase
{
    private const URIS = [
        '/api/tournaments/%s',
        '/api/tournaments/%s/players-of-the-day',
        '/api/tournaments/%s/daily-insights',
        '/api/tournaments/%s/weekly-leaders',
        '/api/tournaments/%s/games',
        '/api/tournaments/%s/teams',
        '/api/games/%s',
        '/api/games/%s/stats',
        '/api/games/%s/insights',
        '/api/leagues/%s/tournaments',
        '/api/leagues/%s/tournaments/1',
        '/api/leagues/1/tournaments/%s',
    ];

    public static function invalidIdUris(): iterable
    {
        foreach (self::URIS as $uri) {
            foreach (['abc', '99999999999999999999'] as $id) {
                $resolved = sprintf($uri, $id);

                yield $resolved => [$resolved];
            }
        }
    }

    // Таблиц в тесте нет намеренно: дошедший до базы запрос упадёт и не даст 404.
    #[DataProvider('invalidIdUris')]
    public function test_invalid_id_returns_not_found(string $uri): void
    {
        $this->getJson($uri)->assertNotFound();
    }

    public function test_numeric_id_still_matches_its_route(): void
    {
        foreach (self::URIS as $uri) {
            foreach (['1', '999999999999999999'] as $id) {
                $route = Route::getRoutes()->match(Request::create(sprintf($uri, $id)));

                $this->assertStringStartsWith('api/', $route->uri());
            }
        }
    }
}
