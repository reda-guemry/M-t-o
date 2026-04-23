<?php

namespace App\Http\Controllers;

use App\Models\WeatherSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class WeatherController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'recentSearches' => WeatherSearch::query()
                ->latest('searched_at')
                ->limit(6)
                ->get(['city', 'country', 'searched_at']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $apiKey = config('services.weatherapi.key');

        if (! $apiKey) {
            return response()->json([
                'message' => 'La clé API WeatherAPI est manquante. Ajoutez WEATHERAPI_KEY dans le fichier .env.',
            ], 500);
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('http://api.weatherapi.com/v1/forecast.json', [
                    'key' => $apiKey,
                    'q' => $validated['city'],
                    'days' => 5,
                    'aqi' => 'no',
                    'alerts' => 'no',
                    'lang' => 'fr',
                ]);

            if ($response->status() === 400) {
                return response()->json([
                    'message' => "Ville non trouvée. Vérifiez l'orthographe et réessayez.",
                ], 404);
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return response()->json([
                    'message' => 'La clé API WeatherAPI est invalide ou inactive.',
                ], 401);
            }

            if ($response->status() === 429) {
                return response()->json([
                    'message' => 'La limite de requêtes WeatherAPI est atteinte. Réessayez plus tard.',
                ], 429);
            }

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Le service météo est momentanément indisponible.',
                ], 502);
            }

            $weather = $response->json();
            $latitude = (float) data_get($weather, 'location.lat');
            $longitude = (float) data_get($weather, 'location.lon');

            WeatherSearch::create([
                'city' => data_get($weather, 'location.name', $validated['city']),
                'country' => data_get($weather, 'location.country'),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'searched_at' => now(),
            ]);

            return response()->json([
                'location' => [
                    'city' => data_get($weather, 'location.name', $validated['city']),
                    'country' => data_get($weather, 'location.country'),
                    'admin' => data_get($weather, 'location.region'),
                    'timezone' => data_get($weather, 'location.tz_id'),
                ],
                'current' => [
                    'temperature' => round((float) data_get($weather, 'current.temp_c')),
                    'humidity' => (int) data_get($weather, 'current.humidity'),
                    'windSpeed' => round((float) data_get($weather, 'current.wind_kph')),
                    'description' => ucfirst((string) data_get($weather, 'current.condition.text', 'Conditions variables')),
                    'icon' => $this->weatherIcon((int) data_get($weather, 'current.condition.code')),
                ],
                'daily' => $this->dailyForecast($weather),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Une erreur est survenue pendant la récupération des données météo.',
            ], 500);
        }
    }

    private function dailyForecast(array $weather): array
    {
        return collect(data_get($weather, 'forecast.forecastday', []))
            ->take(5)
            ->map(function (array $day) {
                return [
                    'date' => data_get($day, 'date'),
                    'min' => round((float) data_get($day, 'day.mintemp_c')),
                    'max' => round((float) data_get($day, 'day.maxtemp_c')),
                    'description' => ucfirst((string) data_get($day, 'day.condition.text', 'Conditions variables')),
                    'icon' => $this->weatherIcon((int) data_get($day, 'day.condition.code')),
                ];
            })
            ->values()
            ->all();
    }

    private function weatherIcon(int $code): string
    {
        return match (true) {
            in_array($code, [1000], true) => 'sun',
            in_array($code, [1003, 1006, 1009], true) => 'cloud-sun',
            in_array($code, [1030, 1135, 1147], true) => 'fog',
            in_array($code, [1063, 1069, 1072, 1150, 1153, 1168, 1171, 1180, 1183, 1186, 1189, 1192, 1195, 1198, 1201, 1240, 1243, 1246], true) => 'rain',
            in_array($code, [1066, 1114, 1117, 1204, 1207, 1210, 1213, 1216, 1219, 1222, 1225, 1237, 1249, 1252, 1255, 1258, 1261, 1264], true) => 'snow',
            in_array($code, [1087, 1273, 1276, 1279, 1282], true) => 'storm',
            default => 'wind',
        };
    }
}
