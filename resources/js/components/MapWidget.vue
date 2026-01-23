<template>
  <div class="w-full h-[400px] shadow-inner border-y border-slate-200 bg-slate-100 relative z-0 group">
    <div id="map" class="w-full h-full"></div>

    <div class="absolute top-4 right-4 z-[1000] flex flex-col gap-2">
      <button v-for="camada in camadas" :key="camada.id" @click="trocarCamada(camada.id)"
        :class="camadaAtiva === camada.id ? 'bg-blue-600 text-white border-blue-400' : 'bg-white/90 backdrop-blur text-slate-600 border-slate-200'"
        class="p-2.5 rounded-xl shadow-xl hover:scale-105 transition-all font-black text-[9px] uppercase tracking-tighter flex items-center gap-2 border">
        <span class="text-base">{{ camada.emoji }}</span> {{ camada.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps(['lat', 'lon', 'iconCode', 'weatherId', 'temp', 'timezone', 'tempMin', 'tempMax', 'nearby']);
const emit = defineEmits(['mapClick']);

let map = null;
let markersLayer = L.layerGroup();
let weatherLayer = null;

const camadaAtiva = ref('precipitation_new');
const camadas = [
  { id: 'precipitation_new', label: 'Chuva/Neve', emoji: '🌧️' },
  { id: 'clouds_new', label: 'Nuvens', emoji: '☁️' },
  { id: 'wind_new', label: 'Vento', emoji: '🌬️' },
  { id: 'pressure_new', label: 'Sol/Pressão', emoji: '☀️' }
];

const getAdvancedIcon = (code, id, temp, timezone, min, max, isMain = true) => {
  let mainEmoji = '❓'; let subEmoji = ''; let animationClass = ''; let bgColor = 'bg-white';
  const t = Math.round(temp);
  const agoraUTC = new Date().getTime() + (new Date().getTimezoneOffset() * 60000);
  const dataCidade = new Date(agoraUTC + (1000 * timezone));
  const horaCidade = dataCidade.getHours();
  const isNoite = horaCidade >= 18 || horaCidade < 5;

  if (id >= 200 && id < 300) { bgColor = 'bg-slate-200 border-yellow-400 border-2'; animationClass = 'animate-shake'; mainEmoji = '⛈️'; }
  else if (id >= 300 && id < 400) { bgColor = 'bg-blue-50 border-blue-200'; animationClass = 'animate-pulse'; mainEmoji = '💧'; }
  else if (id >= 500 && id < 600) { bgColor = 'bg-blue-100 border-blue-300'; animationClass = 'animate-drip'; mainEmoji = '🌧️'; }
  else if (id >= 600 && id < 700) { bgColor = 'bg-cyan-50 border-white'; animationClass = 'animate-spin-reverse'; mainEmoji = '❄️'; }
  else if (id >= 700 && id < 800) { bgColor = 'bg-gray-300 opacity-90'; animationClass = 'animate-pulse-fast'; mainEmoji = '🌫️'; }
  else if (id === 800) {
    if (isNoite) {
      mainEmoji = '🌕'; animationClass = 'animate-pulse'; bgColor = 'bg-slate-900 border-slate-600 text-white';
      if (t >= 30) { mainEmoji = '🔥'; bgColor = 'bg-orange-900 border-red-500 text-white'; }
    } else {
      if (t < 0) { mainEmoji = '🥶'; subEmoji = '❄️'; animationClass = 'animate-shake'; bgColor = 'bg-cyan-100 border-cyan-500'; }
      else if (t >= 0 && t <= 14) { mainEmoji = '🧊'; subEmoji = '❄️'; animationClass = 'animate-pulse'; bgColor = 'bg-cyan-50 border-cyan-300'; }
      else if (t >= 15 && t < 19) { mainEmoji = '😎'; bgColor = 'bg-teal-50'; }
      else if (t >= 19 && t <= 25) { mainEmoji = '🌤️'; animationClass = 'animate-bounce-slow'; bgColor = 'bg-green-50 border-green-200'; }
      else if (t >= 26 && t <= 29) { mainEmoji = '☀️'; animationClass = 'animate-spin-slow'; bgColor = 'bg-yellow-50 border-yellow-400'; }
      else { mainEmoji = '🔥'; animationClass = 'animate-spin-slow'; bgColor = 'bg-orange-100 border-orange-500'; }
    }
  } else if (id > 800) {
    if (t < 0) { mainEmoji = '🥶'; subEmoji = '☁️'; animationClass = 'animate-shake'; bgColor = 'bg-cyan-100 border-cyan-400'; }
    else { mainEmoji = isNoite ? '☁️' : '⛅'; bgColor = isNoite ? 'bg-slate-700 text-white' : 'bg-gray-100'; }
  }

  const sizeClass = isMain ? 'w-16 h-16 text-4xl border-4' : 'w-10 h-10 text-xl border-2';
  const labelClass = isMain ? '-bottom-2 text-[10px] px-2 py-0.5' : '-bottom-3 text-[8px] px-1 py-0';
  const html = `<div class="relative ${sizeClass} flex items-center justify-center rounded-full shadow-2xl ${bgColor} border-white transition-all transform hover:scale-110 hover:z-50 cursor-pointer group">
      <span class="${animationClass} filter drop-shadow-md select-none z-10 flex items-center justify-center">${mainEmoji}</span>
      ${subEmoji ? `<span class="absolute -right-2 -top-2 text-lg filter drop-shadow-md z-20">${subEmoji}</span>` : ''}
      <div class="absolute ${labelClass} bg-slate-900 text-white rounded-full font-bold shadow-md z-30 whitespace-nowrap">${Math.round(temp)}°</div>
    </div>`;
  return L.divIcon({ html, className: '', iconSize: isMain ? [64, 64] : [40, 40], iconAnchor: isMain ? [32, 64] : [20, 20], popupAnchor: [0, -70] });
};

const adicionarCamadaClima = () => {
  if (weatherLayer) map.removeLayer(weatherLayer);
  const API_KEY = "a3f1754aba98434b9bf4a0e99b213a84";

  let filtro = 'chuva-filter';

  // LÓGICA CORRIGIDA: Usa o valor de props.temp para decidir o filtro de sol/calor
  if (camadaAtiva.value === 'precipitation_new' && props.temp < 0) {
    filtro = 'neve-filter';
  } else if (camadaAtiva.value === 'pressure_new') {
    filtro = props.temp >= 30 ? 'calor-filter' : 'sol-filter';
  } else if (camadaAtiva.value === 'clouds_new') {
    filtro = 'nuvens-filter';
  }

  weatherLayer = L.tileLayer(`https://tile.openweathermap.org/map/${camadaAtiva.value}/{z}/{x}/{y}.png?appid=${API_KEY}`, {
    opacity: 0.6,
    zIndex: 10,
    className: filtro
  }).addTo(map);
};

const trocarCamada = (id) => {
  camadaAtiva.value = id;
  adicionarCamadaClima();
};

const updateMarkers = () => {
  markersLayer.clearLayers();
  const mainIcon = getAdvancedIcon(props.iconCode, props.weatherId, props.temp, props.timezone, props.tempMin, props.tempMax, true);
  L.marker([props.lat, props.lon], { icon: mainIcon, zIndexOffset: 1000 }).addTo(markersLayer);

  if (props.nearby && props.nearby.length > 0) {
    props.nearby.forEach(p => {
      const dist = Math.abs(p.coord.lat - props.lat) + Math.abs(p.coord.lon - props.lon);
      if (dist > 0.005) {
        const icon = getAdvancedIcon(p.weather[0].icon, p.weather[0].id, p.main.temp, props.timezone, p.main.temp_min, p.main.temp_max, false);
        L.marker([p.coord.lat, p.coord.lon], { icon }).bindPopup(`<div class="text-center p-1"><b class="uppercase text-xs block mb-1">${p.name}</b><span class="text-blue-500 font-bold text-xs">💧 ${p.main.humidity}% Umidade</span></div>`).addTo(markersLayer);
      }
    });
  }
  markersLayer.addTo(map);
};

const initMap = () => {
  if (map) map.remove();
  map = L.map('map', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  adicionarCamadaClima();
  map.on('click', (e) => emit('mapClick', { lat: e.latlng.lat, lon: e.latlng.lng }));
  updateMarkers();
};

onMounted(initMap);
watch(() => [props.lat, props.lon, props.temp], () => {
  if (map) {
    map.setView([props.lat, props.lon], 11);
    adicionarCamadaClima();
    updateMarkers();
  }
});
</script>

<style>
/* FILTROS DE CAMADA CORRIGIDOS - SEM MAIS VERDE! */
.chuva-filter {
  filter: saturate(2.5) contrast(1.2) hue-rotate(-10deg);
}

.neve-filter {
  filter: saturate(3) brightness(1.2) hue-rotate(180deg);
}

/* Sol (Abaixo de 30°C): Amarelo vibrante */
.sol-filter {
  filter: saturate(3) brightness(1.1) sepia(1) hue-rotate(-15deg);
}

/* Calor (Acima de 30°C): Laranja/Vermelho Intenso */
.calor-filter {
  filter: saturate(5) brightness(1) sepia(0.8) hue-rotate(-35deg);
}

.nuvens-filter {
  filter: grayscale(1) contrast(1.5) brightness(1.2);
}

/* ANIMAÇÕES */
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}

@keyframes spin-reverse {
  from {
    transform: rotate(360deg);
  }

  to {
    transform: rotate(0deg);
  }
}

.animate-spin-reverse {
  animation: spin-reverse 6s linear infinite;
}

@keyframes bounce-slow {

  0%,
  100% {
    transform: translateY(-5%);
  }

  50% {
    transform: translateY(5%);
  }
}

.animate-bounce-slow {
  animation: bounce-slow 3s infinite ease-in-out;
}

@keyframes drip {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(10%);
  }
}

.animate-drip {
  animation: drip 1.5s infinite ease-in-out;
}

@keyframes shake {

  0%,
  100% {
    transform: rotate(0deg);
  }

  25% {
    transform: rotate(-5deg);
  }

  75% {
    transform: rotate(5deg);
  }
}

.animate-shake {
  animation: shake 0.5s infinite;
}

@keyframes pulse-fast {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.6;
  }
}

.animate-pulse-fast {
  animation: pulse-fast 2s infinite;
}
/* No MapWidget.vue */
.dark .leaflet-tile-pane {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}
</style>