<template>
  <div class="w-full h-[400px] shadow-inner border-y border-slate-200 bg-slate-100 relative z-0">
    <div id="map" class="w-full h-full"></div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Props recebidas do Pai
const props = defineProps(['lat', 'lon', 'iconCode', 'weatherId', 'temp', 'timezone', 'tempMin', 'tempMax', 'nearby']);

let map = null;
let markersLayer = L.layerGroup();

// --- 1. LÓGICA DE ÍCONES (Avançada - Versão Gelinho Turbo 🧊❄️) ---
const getAdvancedIcon = (code, id, temp, timezone, min, max, isMain = true) => {
  let mainEmoji = '❓';
  let subEmoji = '';
  let subIconClass = '';
  let animationClass = '';
  let bgColor = 'bg-white';

  const t = Math.round(temp);

  // Horário Local
  const agoraUTC = new Date().getTime() + (new Date().getTimezoneOffset() * 60000);
  const dataCidade = new Date(agoraUTC + (1000 * timezone));
  const horaCidade = dataCidade.getHours();
  const isNoite = horaCidade >= 18 || horaCidade < 5;

  // Lógica de IDs (Chuva, Trovão, etc...)
  if (id >= 200 && id < 300) { bgColor = 'bg-slate-200 border-yellow-400 border-2'; animationClass = 'animate-shake'; mainEmoji = '⛈️'; }
  else if (id >= 300 && id < 400) { bgColor = 'bg-blue-50 border-blue-200'; animationClass = 'animate-pulse'; mainEmoji = '💧'; }
  else if (id >= 500 && id < 600) { bgColor = 'bg-blue-100 border-blue-300'; animationClass = 'animate-drip'; mainEmoji = '🌧️'; }
  else if (id >= 600 && id < 700) { bgColor = 'bg-cyan-50 border-white'; animationClass = 'animate-spin-reverse'; mainEmoji = '❄️'; }
  else if (id >= 700 && id < 800) { bgColor = 'bg-gray-300 opacity-90'; animationClass = 'animate-pulse-fast'; mainEmoji = '🌫️'; }

  // CÉU LIMPO (800)
  else if (id === 800) {
    if (isNoite) {
      mainEmoji = '🌕'; animationClass = 'animate-pulse'; bgColor = 'bg-slate-900 border-slate-600 text-white';
      if (t >= 30) { mainEmoji = '🔥'; bgColor = 'bg-orange-900 border-red-500 text-white'; }
    } else {
      // DIA LIMPO ☀️

      // Congelante (< 0)
      if (t < 0) {
        mainEmoji = '🥶'; subEmoji = '❄️';
        animationClass = 'animate-shake';
        bgColor = 'bg-cyan-100 border-cyan-500';
      }
      // ---> AQUI ESTÁ O GELINHO NOVO (0 a 14 graus) <---
      else if (t >= 0 && t <= 14) {
        mainEmoji = '🧊'; // Cubo Principal
        subEmoji = '❄️';  // Floco de neve de detalhe
        animationClass = 'animate-pulse'; // Pulsando devagar
        bgColor = 'bg-cyan-50 border-cyan-300'; // Fundo azul gelo
      }
      // Fresco (15 a 18)
      else if (t > 14 && t < 19) { mainEmoji = '😎'; bgColor = 'bg-teal-50'; }
      // Agradável (19 a 25)
      else if (t >= 19 && t <= 25) { mainEmoji = '🌤️'; animationClass = 'animate-bounce-slow'; bgColor = 'bg-green-50 border-green-200'; }
      // Quente (26 a 29)
      else if (t >= 26 && t <= 29) { mainEmoji = '☀️'; animationClass = 'animate-spin-slow'; bgColor = 'bg-yellow-50 border-yellow-400'; }
      // Fogo (30+)
      else { mainEmoji = '🔥'; animationClass = 'animate-spin-slow'; bgColor = 'bg-orange-100 border-orange-500'; }
    }
  }

  // NUVENS (> 800) - Com correção para Frio Extremo
  else if (id > 800) {
    // Se estiver congelando (< 0), ganha da nuvem!
    if (t < 0) {
      mainEmoji = '🥶'; subEmoji = '☁️';
      animationClass = 'animate-shake';
      bgColor = 'bg-cyan-100 border-cyan-400';
    } else {
      mainEmoji = isNoite ? '☁️' : '⛅';
      bgColor = isNoite ? 'bg-slate-700 text-white' : 'bg-gray-100';
    }
  }

  // Montagem do HTML
  const sizeClass = isMain ? 'w-16 h-16 text-4xl border-4' : 'w-10 h-10 text-xl border-2';
  const labelClass = isMain ? '-bottom-2 text-[10px] px-2 py-0.5' : '-bottom-3 text-[8px] px-1 py-0';

  const html = `
    <div class="relative ${sizeClass} flex items-center justify-center rounded-full shadow-2xl ${bgColor} border-white transition-all transform hover:scale-110 hover:z-50 cursor-pointer group">
      <span class="${animationClass} filter drop-shadow-md select-none z-10 flex items-center justify-center">${mainEmoji}</span>
      
      ${subEmoji ? `<span class="absolute -right-2 -top-2 text-lg filter drop-shadow-md z-20">${subEmoji}</span>` : ''}
      
      <div class="absolute ${labelClass} bg-slate-900 text-white rounded-full font-bold shadow-md z-30 whitespace-nowrap">
        ${t}°
      </div>
    </div>
  `;

  return L.divIcon({
    html,
    className: '',
    iconSize: isMain ? [64, 64] : [40, 40],
    iconAnchor: isMain ? [32, 64] : [20, 20],
    popupAnchor: [0, -70]
  });
};

// --- 2. ATUALIZA MARCADORES ---
const updateMarkers = () => {
  markersLayer.clearLayers();

  // Marcador Principal
  const mainIcon = getAdvancedIcon(props.iconCode, props.weatherId, props.temp, props.timezone, props.tempMin, props.tempMax, true);
  L.marker([props.lat, props.lon], { icon: mainIcon, zIndexOffset: 1000 }).addTo(markersLayer);

  // Marcadores Vizinhos
  if (props.nearby && props.nearby.length > 0) {
    props.nearby.forEach(point => {
      const dist = Math.abs(point.coord.lat - props.lat) + Math.abs(point.coord.lon - props.lon);
      if (dist > 0.005) {
        const icon = getAdvancedIcon(
          point.weather[0].icon,
          point.weather[0].id,
          point.main.temp,
          props.timezone,
          point.main.temp_min,
          point.main.temp_max,
          false
        );
        L.marker([point.coord.lat, point.coord.lon], { icon: icon })
          .bindPopup(`<b class="uppercase text-xs">${point.name}</b>`)
          .addTo(markersLayer);
      }
    });
  }
  markersLayer.addTo(map);
};

// --- 3. INICIA O MAPA ---
const initMap = () => {
  if (map) map.remove();

  map = L.map('map', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 11);

  // CAMADA 1: RUAS (Obrigatório)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  // CAMADA 2: CHUVA (Sua chave já está configurada aqui)
  const API_KEY = "a3f1754aba98434b9bf4a0e99b213a84";

  L.tileLayer(`https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=${API_KEY}`, {
    opacity: 0.5,
    zIndex: 10
  }).addTo(map);

  updateMarkers();
};

onMounted(() => { initMap(); });

watch(() => [props.lat, props.lon, props.weatherId, props.temp, props.nearby], () => {
  if (map) {
    map.setView([props.lat, props.lon], 11);
    updateMarkers();
  }
});
</script>

<style>
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
</style>