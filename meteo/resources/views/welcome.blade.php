<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Météo</title>
        <meta
            name="description"
            content="Application météo responsive pour consulter la météo actuelle et les prévisions sur plusieurs jours."
        >
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <main class="page-shell">
            <section class="hero-card">
                <div class="hero-copy">
                    <p class="eyebrow">Application météo</p>
                    <h1>Météo en temps réel</h1>
                    <p class="hero-text">
                        Recherchez une ville, affichez les conditions actuelles et parcourez les prévisions des prochains jours
                        dans une interface claire, rapide et responsive.
                    </p>

                    <form class="search-form" id="weather-form">
                        <label class="sr-only" for="city">Ville</label>
                        <input
                            id="city"
                            name="city"
                            type="text"
                            placeholder="Exemple : Casablanca, Paris, Rabat"
                            autocomplete="off"
                            required
                        >
                        <button type="submit" id="search-button">Rechercher</button>
                    </form>

                    <p class="form-hint">Source des données : Open-Meteo. Aucune clé API nécessaire.</p>

                    <div class="recent-searches">
                        <span>Dernières recherches</span>
                        <div class="recent-search-list">
                            @forelse ($recentSearches as $search)
                                <button
                                    type="button"
                                    class="chip"
                                    data-city="{{ $search->city }}"
                                >
                                    {{ $search->city }}@if($search->country), {{ $search->country }}@endif
                                </button>
                            @empty
                                <span class="muted">Aucune recherche enregistrée pour le moment.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="hero-panel">
                    <div class="status-card">
                        <p class="status-label">État</p>
                        <p class="status-value" id="status-text">Prêt pour une recherche</p>
                    </div>
                    <div class="weather-visual" aria-hidden="true">
                        <span class="visual-sun"></span>
                        <span class="visual-cloud visual-cloud-a"></span>
                        <span class="visual-cloud visual-cloud-b"></span>
                        <span class="visual-rain visual-rain-a"></span>
                        <span class="visual-rain visual-rain-b"></span>
                        <span class="visual-rain visual-rain-c"></span>
                    </div>
                </div>
            </section>

            <section class="results-grid">
                <article class="panel current-panel">
                    <div class="panel-header">
                        <div>
                            <p class="panel-kicker">Météo actuelle</p>
                            <h2 id="location-name">Choisissez une ville</h2>
                            <p id="location-meta" class="panel-meta">Les détails apparaîtront ici après la recherche.</p>
                        </div>
                        <div class="weather-icon weather-icon--sun" id="current-icon" aria-label="Icône météo"></div>
                    </div>

                    <div class="temperature-row">
                        <div>
                            <p class="temperature" id="current-temp">--°</p>
                            <p class="description" id="current-description">En attente de données</p>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <span>Humidité</span>
                            <strong id="current-humidity">--%</strong>
                        </div>
                        <div class="stat-card">
                            <span>Vent</span>
                            <strong id="current-wind">-- km/h</strong>
                        </div>
                    </div>

                    <div class="feedback" id="feedback-box" aria-live="polite">
                        Saisissez une ville pour afficher la météo actuelle et les prévisions.
                    </div>
                </article>

                <article class="panel forecast-panel">
                    <div class="panel-header compact">
                        <div>
                            <p class="panel-kicker">Prévisions</p>
                            <h2>5 prochains jours</h2>
                        </div>
                    </div>
                    <div class="forecast-list" id="forecast-list">
                        <div class="forecast-empty">Les cartes de prévision apparaîtront ici.</div>
                    </div>
                </article>
            </section>
        </main>

        <script>
            window.weatherApp = {
                endpoint: "{{ route('weather.search') }}",
            };
        </script>
    </body>
</html>
