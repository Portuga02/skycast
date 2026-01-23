<template>
  <div class="relative w-full h-full">
    <div id="mapContainer" class="w-full h-full z-0 relative cursor-pointer outline-none"></div>

    <div class="absolute top-4 right-4 z-[500] flex flex-col gap-2">
      <button v-for="camada in camadasDisponiveis" :key="camada.id" @click="trocarCamada(camada.id)"
        class="w-10 h-10 md:w-auto md:h-auto md:px-4 md:py-2 rounded-xl backdrop-blur-md border shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group"
        :class="camadaAtiva === camada.id
          ? 'bg-blue-600/90 border-blue-400 text-white shadow-blue-500/30'
          : 'bg-white/80 dark:bg-slate-900/80 border-white/20 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:scale-105'">

        <span class="text-lg">{{ camada.icone }}</span>
        <span class="hidden md:block text-[10px] font-bold uppercase tracking-wider">{{ camada.nome }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps([
  'lat', 'lon', 'temp', 'iconCode', 'weatherId', 'timezone', 'nearby', 'isDark', 'isDay'
]);
const emit = defineEmits(['mapClick']);

const mapContainer = ref(null);
let map = null;
let tileLayer = null;      // Camada Base (CartoDB)
let weatherLayer = null;   // Camada Clima (OpenWeather)
let markersGroup = null;

const camadasDisponiveis = [
  { id: null, nome: 'Limpo', icone: '❌' },
  { id: 'precipitation_new', nome: 'Chuva', icone: '☔' },
  { id: 'clouds_new', nome: 'Nuvens', icone: '☁️' },
  { id: 'temp_new', nome: 'Temp', icone: '🌡️' },
  { id: 'wind_new', nome: 'Vento', icone: '🌬️' }
];

const camadaAtiva = ref(null); // Começa sem camada extra

const tileUrls = {
  dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
  light: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
};

// --- LÓGICA DE TROCA DE CAMADAS ---
const trocarCamada = (layerId) => {
  camadaAtiva.value = layerId;

  // 1. Remove a camada de clima anterior se existir
  if (weatherLayer) {
    map.removeLayer(weatherLayer);
    weatherLayer = null;
  }

  // 2. Se o usuário escolheu "Limpo", para por aqui
  if (!layerId) return;

  // 3. Adiciona a nova camada via Proxy do Laravel
  const urlProxy = `/api/map-tile/${layerId}/{z}/{x}/{y}`;
  weatherLayer = L.tileLayer(urlProxy, {
    opacity: 0.8,
    zIndex: 10 // Garante que fique acima do mapa base
  }).addTo(map);
};

// --- ICONES DOS MARCADORES ---
const obterIconeVisual = (iconCode, isMainMarker = false) => {
  let codigoFinal = iconCode;
  if (isMainMarker) {
    if (props.isDay === true) codigoFinal = iconCode.replace('n', 'd');
    else if (props.isDay === false) codigoFinal = iconCode.replace('d', 'n');
  }
  const mapa = {
    '01d': '☀️', '02d': '🌤️', '03d': '☁️', '04d': '☁️', '09d': '🌧️', '10d': '🌦️', '11d': '⛈️', '13d': '❄️', '50d': '🌫️',
    '01n': '🌙', '02n': '☁️🌙', '03n': '☁️', '04n': '☁️', '09n': '🌧️', '10n': '🌧️', '11n': '⛈️', '13n': '❄️', '50n': '🌫️'
  };
  return mapa[codigoFinal] || '🌡️';
};

const updateBaseTiles = () => {
  if (tileLayer) {
    tileLayer.setUrl(props.isDark ? tileUrls.dark : tileUrls.light);
  }
};

const createCustomMarker = (lat, lon, temp, icon, isMain = false) => {
  const emoji = obterIconeVisual(icon, isMain);
  const html = `
    <div class="relative flex items-center justify-center transition-transform hover:scale-110">
      <div class="w-10 h-10 rounded-full shadow-2xl flex items-center justify-center text-xl border-2 
                  ${isMain ? 'bg-blue-600 border-white text-white z-50 scale-125' : 'bg-white/90 border-slate-200 text-slate-800 backdrop-blur-md'}">
        ${emoji}
      </div>
      ${isMain ? '<div class="absolute -bottom-1 w-2 h-2 bg-blue-600 rotate-45"></div>' : ''} 
      <div class="absolute -bottom-6 bg-slate-900 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
        ${Math.round(temp)}°
      </div>
    </div>
  `;

  const iconObj = L.divIcon({
    className: 'custom-pin group',
    html: html,
    iconSize: [40, 40],
    iconAnchor: [20, 40]
  });

  const marker = L.marker([lat, lon], { icon: iconObj }).addTo(markersGroup);

  marker.on('click', (e) => {
    L.DomEvent.stopPropagation(e);
    map.flyTo([lat, lon], 12, { duration: 1.5 });
    emit('mapClick', { lat, lon });
  });
};

const renderMapData = () => {
  if (!map) return;
  markersGroup.clearLayers();
  createCustomMarker(props.lat, props.lon, props.temp, props.iconCode, true);
  if (props.nearby) {
    props.nearby.forEach(city => {
      createCustomMarker(city.coord.lat, city.coord.lon, city.main.temp, city.weather[0].icon, false);
    });
  }
};

onMounted(() => {
  map = L.map('mapContainer', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 11);
  tileLayer = L.tileLayer(props.isDark ? tileUrls.dark : tileUrls.light, { maxZoom: 19 }).addTo(map);
  markersGroup = L.layerGroup().addTo(map);

  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    map.flyTo([lat, lng], 12, { duration: 1.5 });
    emit('mapClick', { lat: lat, lon: lng });
  });

  renderMapData();
});

watch(() => props.isDark, updateBaseTiles);
watch(() => [props.lat, props.lon, props.isDay], ([lat, lon]) => {
  if (map) {
    map.flyTo([lat, lon], 11, { duration: 2.0 });
    renderMapData();
  }
});
</script>

<style>
#mapContainer {
  background: transparent;
}

.leaflet-div-icon {
  background: transparent;
  border: none;
}

/* Remove borda azul ao clicar no mapa */
.leaflet-container:focus {
  outline: none;
}
</style>