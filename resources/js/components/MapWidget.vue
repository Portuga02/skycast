<template>
  <div class="w-full h-[400px] shadow-inner border-y border-slate-200 bg-slate-100 relative z-0">
    <div id="map" class="w-full h-full"></div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Recebendo weatherId agora!
const props = defineProps(['lat', 'lon', 'iconCode', 'weatherId', 'temp', 'timezone', 'tempMin', 'tempMax']);
let map = null;
let marker = null;

const getAdvancedIcon = (code, id, temp, timezone, min, max) => {
  let mainEmoji = '❓';
  let subEmoji = ''; 
  let subIconClass = ''; 
  let animationClass = '';
  let bgColor = 'bg-white';
  
  const t = Math.round(temp);
  
  // 1. HORÁRIO LOCAL
  const agoraUTC = new Date().getTime() + (new Date().getTimezoneOffset() * 60000);
  const dataCidade = new Date(agoraUTC + (1000 * timezone));
  const horaCidade = dataCidade.getHours();
  const isNoite = horaCidade >= 18 || horaCidade < 5;

  // --- LÓGICA MESTRA DOS IDs (OpenWeatherMap Condition Codes) ---
  
  // GRUPO 2xx: TEMPESTADE ⛈️
  if (id >= 200 && id < 300) {
    bgColor = 'bg-slate-200 border-yellow-400 border-2';
    animationClass = 'animate-shake';
    
    if (id >= 200 && id <= 202) mainEmoji = '⛈️'; // Tempestade com chuva
    else if (id >= 210 && id <= 221) mainEmoji = '🌩️'; // Só raios
    else if (id >= 230 && id <= 232) mainEmoji = '🌦️⚡'; // Garoa com raio
    else mainEmoji = '⛈️';
  }
  
  // GRUPO 3xx: GAROA (Drizzle) 💧
  else if (id >= 300 && id < 400) {
    bgColor = 'bg-blue-50 border-blue-200';
    animationClass = 'animate-pulse';
    
    if (id === 300 || id === 301) mainEmoji = '🚿'; // Garoa leve
    else if (id >= 302 && id <= 321) mainEmoji = '🌧️'; // Garoa pesada
    else mainEmoji = '💧';
  }

  // GRUPO 5xx: CHUVA 🌧️
  else if (id >= 500 && id < 600) {
    bgColor = 'bg-blue-100 border-blue-300';
    animationClass = 'animate-drip';
    
    if (id === 500) mainEmoji = '🌦️'; // Chuva leve
    else if (id === 501) mainEmoji = '🌧️'; // Chuva moderada
    else if (id === 502 || id === 503 || id === 504) { 
        mainEmoji = '🌊'; // Chuva Extrema/Pesada
        animationClass = 'animate-bounce-slow';
    }
    else if (id === 511) {
        mainEmoji = '🥶'; subEmoji = '🌧️'; // Chuva Congelante (Freezing Rain)
        bgColor = 'bg-cyan-100 border-blue-400';
    }
    else if (id >= 520 && id <= 531) mainEmoji = '🚿'; // Chuva de banho (Shower rain)
    else mainEmoji = '🌧️';
  }

  // GRUPO 6xx: NEVE ❄️
  else if (id >= 600 && id < 700) {
    bgColor = 'bg-cyan-50 border-white';
    animationClass = 'animate-spin-reverse';
    
    if (id === 600) mainEmoji = '🌨️'; // Neve leve
    else if (id === 601) mainEmoji = '❄️'; // Neve
    else if (id === 602) { mainEmoji = '☃️'; animationClass = 'animate-bounce-slow'; } // Neve pesada
    else if (id >= 611 && id <= 616) mainEmoji = '🧊🌧️'; // Chuva com neve (Sleet)
    else mainEmoji = '❄️';
  }

  // GRUPO 7xx: ATMOSFERA (Os estranhos) 🌫️
  else if (id >= 700 && id < 800) {
    bgColor = 'bg-gray-300 opacity-90';
    animationClass = 'animate-pulse-fast';

    if (id === 711) mainEmoji = '💨'; // Fumaça (Smoke)
    else if (id === 721) mainEmoji = '😶‍🌫️'; // Haze
    else if (id === 731 || id === 761) mainEmoji = '🏜️'; // Poeira/Areia
    else if (id === 741) mainEmoji = '🌫️'; // Neblina (Fog)
    else if (id === 762) mainEmoji = '🌋'; // Cinzas Vulcânicas
    else if (id === 771) mainEmoji = '🌬️'; // Squalls (Vendaval)
    else if (id === 781) { mainEmoji = '🌪️'; animationClass = 'animate-spin-fast'; bgColor='bg-slate-400 border-red-500'; } // TORNADO!
    else mainEmoji = '🌫️';
  }

  // GRUPO 800: CÉU LIMPO (Clear) - AQUI ENTRA A LÓGICA DE TEMP/HORA ☀️/🌕
  else if (id === 800) {
     if (isNoite) {
        mainEmoji = '🌕'; 
        animationClass = 'animate-pulse';
        bgColor = 'bg-slate-900 border-slate-600 text-white';
        if (t >= 30) { mainEmoji = '🔥'; bgColor = 'bg-orange-900 border-red-500 text-white'; }
     } else {
        // Dia Limpo: Regras de Temperatura
        if (t < 0) { mainEmoji = '🥶'; subEmoji = '❄️'; animationClass = 'animate-shake'; bgColor = 'bg-cyan-100 border-cyan-500'; }
        else if (t >= 0 && t <= 14) { mainEmoji = '🧊'; animationClass = 'animate-pulse'; bgColor = 'bg-blue-50 border-blue-200'; }
        else if (t > 14 && t < 19) { mainEmoji = '😎'; bgColor = 'bg-teal-50'; }
        else if (t >= 19 && t <= 25) { mainEmoji = '🌤️'; animationClass = 'animate-bounce-slow'; bgColor = 'bg-green-50 border-green-200'; }
        else if (t >= 26 && t <= 29) { mainEmoji = '☀️'; animationClass = 'animate-spin-slow'; bgColor = 'bg-yellow-50 border-yellow-400'; }
        else { mainEmoji = '🔥'; animationClass = 'animate-spin-slow'; bgColor = 'bg-orange-100 border-orange-500'; }
     }
  }

  // GRUPO 80x: NUVENS ☁️
  else if (id > 800) {
      if (id === 801) { mainEmoji = isNoite ? '☁️🌑' : '🌤️'; bgColor = 'bg-blue-50'; } // Poucas nuvens (11-25%)
      else if (id === 802) { mainEmoji = '⛅'; bgColor = 'bg-gray-50'; } // Nuvens dispersas (25-50%)
      else if (id === 803) { mainEmoji = '☁️'; animationClass = 'animate-bounce-slow'; bgColor = 'bg-gray-100'; } // Nublado (51-84%)
      else if (id === 804) { mainEmoji = '☁️'; bgColor = 'bg-gray-300 border-gray-400'; } // Nublado total (Overcast)
  }

  // --- TENDÊNCIA (Termômetros) ---
  if (!subEmoji && id === 800) { // Só mostra tendência se céu estiver limpo pra não poluir
      if (t >= 30 && max > t + 2) {
          subEmoji = '🌡️'; subIconClass = 'text-red-600 absolute -right-2 bottom-0 text-2xl filter drop-shadow animate-pulse';
      }
      else if (t <= 14 && min < t - 2) {
          subEmoji = '🌡️'; subIconClass = 'text-blue-500 absolute -right-2 bottom-0 text-2xl filter drop-shadow animate-pulse';
      }
  }

  const html = `
    <div class="relative w-16 h-16 flex items-center justify-center rounded-full shadow-2xl ${bgColor} border-4 border-white transition-all transform hover:scale-110">
      <span class="text-4xl ${animationClass} filter drop-shadow-md select-none z-10 flex items-center justify-center">${mainEmoji}</span>
      ${subEmoji ? `<span class="${subIconClass || 'absolute -right-1 bottom-0 text-lg'} z-20">${subEmoji}</span>` : ''}
      <div class="absolute -bottom-2 bg-slate-900 text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-md z-30">
        ${t}°C
      </div>
    </div>
  `;

  return L.divIcon({ html, className: '', iconSize: [64, 64], iconAnchor: [32, 64], popupAnchor: [0, -70] });
};

const initMap = () => {
  if (map) map.remove();
  map = L.map('map', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  // L.tileLayer('/api/map-tile/{z}/{x}/{y}', { opacity: 0.5, zIndex: 10 }).addTo(map); // Chuva
  marker = L.marker([props.lat, props.lon], { 
    icon: getAdvancedIcon(props.iconCode, props.weatherId, props.temp, props.timezone, props.tempMin, props.tempMax) 
  }).addTo(map);
};

onMounted(() => { initMap(); });

watch(() => [props.lat, props.lon, props.weatherId, props.temp], () => {
  if (map) {
    map.setView([props.lat, props.lon], 11);
    if (marker) {
      marker.setLatLng([props.lat, props.lon]);
      marker.setIcon(getAdvancedIcon(props.iconCode, props.weatherId, props.temp, props.timezone, props.tempMin, props.tempMax));
    }
  }
});
</script>

<style>
/* Adicionei animação rápida para tornado */
@keyframes spin-fast { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-spin-fast { animation: spin-fast 1s linear infinite; }

/* As outras você já tem */
@keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 8s linear infinite; }
@keyframes spin-reverse { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }
.animate-spin-reverse { animation: spin-reverse 6s linear infinite; }
@keyframes bounce-slow { 0%, 100% { transform: translateY(-5%); } 50% { transform: translateY(5%); } }
.animate-bounce-slow { animation: bounce-slow 3s infinite ease-in-out; }
@keyframes drip { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(10%); } }
.animate-drip { animation: drip 1.5s infinite ease-in-out; }
@keyframes shake { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-5deg); } 75% { transform: rotate(5deg); } }
.animate-shake { animation: shake 0.5s infinite; }
@keyframes pulse-fast { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
.animate-pulse-fast { animation: pulse-fast 2s infinite; }
</style>