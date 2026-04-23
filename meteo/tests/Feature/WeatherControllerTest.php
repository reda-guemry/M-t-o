<?php

use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('home page loads', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Application météo');
});

test('weather search returns formatted data and persists search', function () {
    config(['services.weatherapi.key' => 'test-api-key']);

    Http::fake([
        'http://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'location' => [
                'name' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'country' => 'Morocco',
                'lat' => 33.57,
                'lon' => -7.59,
                'tz_id' => 'Africa/Casablanca',
            ],
            'current' => [
                'temp_c' => 24.4,
                'humidity' => 63,
                'wind_kph' => 18.1,
                'condition' => [
                    'text' => 'partiellement nuageux',
                    'code' => 1003,
                ],
            ],
            'forecast' => [
                'forecastday' => [
                    [
                        'date' => '2026-04-03',
                        'day' => [
                            'maxtemp_c' => 25.1,
                            'mintemp_c' => 17.3,
                            'condition' => [
                                'text' => 'partiellement nuageux',
                                'code' => 1003,
                            ],
                        ],
                    ],
                    [
                        'date' => '2026-04-04',
                        'day' => [
                            'maxtemp_c' => 22.4,
                            'mintemp_c' => 15.2,
                            'condition' => [
                                'text' => 'pluie légère',
                                'code' => 1183,
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->getJson('/weather?city=Casablanca');

    $response
        ->assertOk()
        ->assertJsonPath('location.city', 'Casablanca')
        ->assertJsonPath('location.country', 'Morocco')
        ->assertJsonPath('current.temperature', 24)
        ->assertJsonPath('current.description', 'Partiellement nuageux')
        ->assertJsonPath('current.windSpeed', 18)
        ->assertJsonCount(2, 'daily');

    $this->assertDatabaseHas('weather_searches', [
        'city' => 'Casablanca',
        'country' => 'Morocco',
    ]);
});

test('weather search returns not found for unknown city', function () {
    config(['services.weatherapi.key' => 'test-api-key']);

    Http::fake([
        'http://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'error' => [
                'code' => 1006,
                'message' => 'No matching location found.',
            ],
        ], 400),
    ]);

    $response = $this->getJson('/weather?city=zzz');

    $response
        ->assertNotFound()
        ->assertJsonPath('message', "Ville non trouvée. Vérifiez l'orthographe et réessayez.");

    $this->assertDatabaseCount('weather_searches', 0);
});

test('weather search reports invalid api key', function () {
    config(['services.weatherapi.key' => 'invalid-api-key']);

    Http::fake([
        'http://api.weatherapi.com/v1/forecast.json*' => Http::response([
            'error' => [
                'code' => 2008,
                'message' => 'API key has been disabled.',
            ],
        ], 401),
    ]);

    $response = $this->getJson('/weather?city=Agadir');

    $response
        ->assertUnauthorized()
        ->assertJsonPath('message', 'La clé API WeatherAPI est invalide ou inactive.');

    $this->assertDatabaseCount('weather_searches', 0);
});

test('weather search reports missing api key', function () {
    config(['services.weatherapi.key' => null]);

    $response = $this->getJson('/weather?city=Casablanca');

    $response
        ->assertStatus(500)
        ->assertJsonPath('message', 'La clé API WeatherAPI est manquante. Ajoutez WEATHERAPI_KEY dans le fichier .env.');
});
