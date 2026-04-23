import './bootstrap';

const form = document.querySelector('#weather-form');
const cityInput = document.querySelector('#city');
const searchButton = document.querySelector('#search-button');
const statusText = document.querySelector('#status-text');
const locationName = document.querySelector('#location-name');
const locationMeta = document.querySelector('#location-meta');
const currentIcon = document.querySelector('#current-icon');
const currentTemp = document.querySelector('#current-temp');
const currentDescription = document.querySelector('#current-description');
const currentHumidity = document.querySelector('#current-humidity');
const currentWind = document.querySelector('#current-wind');
const feedbackBox = document.querySelector('#feedback-box');
const forecastList = document.querySelector('#forecast-list');
const recentSearchButtons = document.querySelectorAll('[data-city]');

const iconMap = {
    sun: 'Soleil',
    'cloud-sun': 'Nuages et soleil',
    fog: 'Brouillard',
    rain: 'Pluie',
    snow: 'Neige',
    storm: 'Orage',
    wind: 'Vent',
};

function setLoadingState(isLoading) {
    searchButton.disabled = isLoading;
    searchButton.textContent = isLoading ? 'Chargement...' : 'Rechercher';
    statusText.textContent = isLoading ? 'Recherche en cours' : 'Prêt pour une recherche';
}

function setFeedback(message, type = 'neutral') {
    feedbackBox.textContent = message;
    feedbackBox.dataset.state = type;
}

function formatDate(dateString) {
    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    }).format(new Date(dateString));
}

function weatherIconClass(icon) {
    return `weather-icon weather-icon--${icon}`;
}

function renderForecast(days) {
    if (!days.length) {
        forecastList.innerHTML = '<div class="forecast-empty">Aucune prévision disponible.</div>';
        return;
    }

    forecastList.innerHTML = days
        .map(
            (day) => `
                <article class="forecast-card">
                    <div>
                        <p class="forecast-day">${formatDate(day.date)}</p>
                        <p class="forecast-desc">${day.description}</p>
                    </div>
                    <div class="forecast-symbol ${weatherIconClass(day.icon)}" aria-label="${iconMap[day.icon] ?? 'Météo'}"></div>
                    <div class="forecast-temps">
                        <strong>${day.max}°</strong>
                        <span>${day.min}°</span>
                    </div>
                </article>
            `,
        )
        .join('');
}

function renderWeather(data) {
    const cityLine = [data.location.city, data.location.country].filter(Boolean).join(', ');
    const metaLine = [data.location.admin, data.location.timezone].filter(Boolean).join(' · ');

    locationName.textContent = cityLine || 'Ville sélectionnée';
    locationMeta.textContent = metaLine || 'Informations régionales indisponibles';
    currentIcon.className = weatherIconClass(data.current.icon);
    currentIcon.setAttribute('aria-label', iconMap[data.current.icon] ?? 'Météo');
    currentTemp.textContent = `${data.current.temperature}°`;
    currentDescription.textContent = data.current.description;
    currentHumidity.textContent = `${data.current.humidity}%`;
    currentWind.textContent = `${data.current.windSpeed} km/h`;
    statusText.textContent = `Mise à jour pour ${data.location.city}`;
    setFeedback('Données météo chargées avec succès.', 'success');
    renderForecast(data.daily);
}

async function fetchWeather(city) {
    setLoadingState(true);
    setFeedback('Connexion au service météo...', 'neutral');

    try {
        const url = new URL(window.weatherApp.endpoint, window.location.origin);
        url.searchParams.set('city', city);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Impossible de récupérer la météo.');
        }

        renderWeather(payload);
    } catch (error) {
        setFeedback(error.message, 'error');
        statusText.textContent = 'Recherche interrompue';
    } finally {
        setLoadingState(false);
    }
}

if (form) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const city = cityInput.value.trim();

        if (!city) {
            setFeedback('Veuillez saisir une ville avant de lancer la recherche.', 'error');
            return;
        }

        fetchWeather(city);
    });
}

recentSearchButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const city = button.dataset.city;

        cityInput.value = city;
        fetchWeather(city);
    });
});
