<template>
  <div class="w-full h-full flex flex-row gap-4">

    <div class="flex-1 rounded-[2.5rem] overflow-hidden shadow-2xl border-4 relative transition-all duration-500 z-0"
      :class="isDark ? 'border-slate-800 shadow-blue-900/10' : 'border-white shadow-slate-200'">

      <div id="mapContainer"
        class="w-full h-full z-0 relative cursor-pointer outline-none bg-slate-200 dark:bg-slate-900"></div>

    </div>

    <div class="flex flex-col gap-3 pt-10 relative z-50">

      <button v-for="camada in camadasDisponiveis" :key="camada.id" @click="trocarCamada(camada.id)" class="
          w-12 h-12 rounded-2xl transition-all duration-300 flex items-center justify-center shadow-lg border-2 group relative
        " :class="camadaAtiva === camada.id
          ? 'bg-blue-600 border-blue-500 text-white shadow-blue-500/40 scale-110'
          : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:scale-105 hover:border-blue-200 dark:hover:border-slate-600'
          ">

        <span class="text-3xl filter drop-shadow-sm group-hover:animate-pulse">{{ camada.icone }}</span>

        <span class="
          absolute right-full mr-4 px-3 py-1.5 rounded-xl 
          bg-slate-900 text-white text-[10px] font-bold uppercase tracking-wider
          opacity-0 group-hover:opacity-100 transition-opacity duration-200 
          pointer-events-none whitespace-nowrap shadow-2xl z-50 min-w-max
        ">
          {{ camada.nome }}
          <span class="absolute top-1/2 -right-1.5 -mt-1 w-2.5 h-2.5 bg-slate-900 rotate-45"></span>
        </span>

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
let tileLayer = null;
let weatherLayer = null;
let markersGroup = null;

const camadasDisponiveis = [
  { id: null, nome: 'Limpo', icone: '❌' },
  { id: 'precipitation_new', nome: 'Chuva', icone: '☔' },
  { id: 'clouds_new', nome: 'Nuvens', icone: '☁️' },
  { id: 'temp_new', nome: 'Temp', icone: '🌡️' },
  { id: 'wind_new', nome: 'Vento', icone: '🌬️' }
];

const camadaAtiva = ref(null);

// --- MAPAS BASE (MUDANÇA AQUI) ---
const tileUrls = {
  // Usamos o mapa CLARO e COLORIDO do OSM aqui
  dark: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
  light: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
};

const trocarCamada = (layerId) => {
  camadaAtiva.value = layerId;
  if (weatherLayer) {
    map.removeLayer(weatherLayer);
    weatherLayer = null;
  }
  if (!layerId) return;

  const urlProxy = `/api/map-tile/${layerId}/{z}/{x}/{y}`;
  weatherLayer = L.tileLayer(urlProxy, {
    opacity: 0.8,
    zIndex: 10
  }).addTo(map);
};

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

const updateTiles = () => {
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
  setTimeout(() => { if (map) map.invalidateSize(); }, 100);
  map = L.map('mapContainer', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 11);

  // Inicia com o tile correto
  const urlInicial = props.isDark ? tileUrls.dark : tileUrls.light;
  tileLayer = L.tileLayer(urlInicial, { maxZoom: 19 }).addTo(map);

  markersGroup = L.layerGroup().addTo(map);

  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    map.flyTo([lat, lng], 12, { duration: 1.5 });
    emit('mapClick', { lat: lat, lon: lng });
  });

  renderMapData();
});

watch(() => props.isDark, updateTiles);
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

.leaflet-container:focus {
  outline: none;
}
</style>