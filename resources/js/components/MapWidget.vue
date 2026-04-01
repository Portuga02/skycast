<template>
  <div class="w-full h-full min-h-[450px] lg:min-h-[620px] flex flex-col md:flex-row gap-4 relative overflow-hidden">
    <div class="flex-1 rounded-[2.5rem] overflow-hidden shadow-2xl border-4 transition-all duration-500 relative z-10"
      :class="isDark ? 'border-slate-800 shadow-blue-900/10' : 'border-white shadow-slate-200'">
      <div id="mapContainer" class="w-full h-full outline-none bg-slate-200 dark:bg-slate-900"></div>
    </div>

    <div class="controles-container z-20">
      <button @click="centralizarMapa"
        class="botao-controle bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 hover:border-blue-400 mb-2 md:mb-4">
        
        <img src="https://cdn.jsdelivr.net/gh/microsoft/fluentui-emoji@main/assets/Compass/3D/compass_3d.png"
          class="w-8 h-8 object-contain transition-transform duration-500 group-hover:rotate-[360deg]"
          alt="Centralizar">

        <span class="tooltip">Centralizar</span>
      </button>

      <button v-for="camada in camadasDisponiveis" :key="camada.id" @click="trocarCamada(camada.id)"
        class="botao-controle"
        :class="[
          camadaAtiva === camada.id
            ? 'border-blue-500 bg-blue-50 dark:bg-slate-700 shadow-blue-500/30 scale-110 ring-2 ring-blue-400/50'
            : 'border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-300'
        ]">

        <img :src="camada.img" class="w-8 h-8 object-contain transition-transform group-hover:scale-110" alt="Icone">
        <span class="tooltip">{{ camada.nome }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps(['lat', 'lon', 'temp', 'iconCode', 'weatherId', 'timezone', 'nearby', 'isDark', 'isDay', 'uv']);
const emit = defineEmits(['mapClick']);

let map = null;
let markersGroup = null;

const baseCdn = 'https://cdn.jsdelivr.net/gh/microsoft/fluentui-emoji@main/assets';

const camadasDisponiveis = [
  { id: null, nome: 'Limpo', img: `${baseCdn}/Cross%20mark/3D/cross_mark_3d.png` },
  { id: 'precipitation_new', nome: 'Chuva', img: `${baseCdn}/Umbrella%20with%20rain%20drops/3D/umbrella_with_rain_drops_3d.png` },
  { id: 'snow_new', nome: 'Neve', img: `${baseCdn}/Snowflake/3D/snowflake_3d.png` },
  { id: 'clouds_new', nome: 'Nuvens', img: `${baseCdn}/Cloud/3D/cloud_3d.png` },
  { id: 'temp_new', nome: 'Calor', img: `${baseCdn}/Thermometer/3D/thermometer_3d.png` },
  { id: 'pressure_new', nome: 'Pressão', img: `${baseCdn}/Stopwatch/3D/stopwatch_3d.png` },
  { id: 'wind_new', nome: 'Vento', img: `${baseCdn}/Wind%20face/3D/wind_face_3d.png` }
];

const camadaAtiva = ref(null);

const createCustomMarker = (lat, lon, temp, icon, weatherId, isMain = false) => {
  const id = parseInt(weatherId);
  const safeIcon = icon || '02d';
  const owIconUrl = `https://openweathermap.org/img/wn/${safeIcon}@2x.png`;
  
  const icons3D = {
    thunderstorm: `${baseCdn}/Cloud%20with%20lightning%20and%20rain/3D/cloud_with_lightning_and_rain_3d.png`,
    rain: `${baseCdn}/Cloud%20with%20rain/3D/cloud_with_rain_3d.png`,
    sunRain: `${baseCdn}/Sun%20behind%20rain%20cloud/3D/sun_behind_rain_cloud_3d.png`,
    cloudy: `${baseCdn}/Cloud/3D/cloud_3d.png`,
    sunCloud: `${baseCdn}/Sun%20behind%20cloud/3D/sun_behind_cloud_3d.png`,
    sun: `${baseCdn}/Sun/3D/sun_3d.png`,
    moon: `${baseCdn}/Full%20moon/3D/full_moon_3d.png`,
    fog: `${baseCdn}/Fog/3D/fog_3d.png`
  };

  const animatedFluentEmojiBase = 'https://cdn.jsdelivr.net/gh/Tarikul-Islam-Anik/Animated-Fluent-Emojis/Emojis/Smilies';
  // --- NOVIDADE: Fonte Animada focado em CLIMA ⛈️ ---
  const animatedWeatherBase = 'https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Travel%20and%20places';

  let iconUrl = icons3D.sun;
  let bgClass = 'bg-white border-slate-200';

  if (id >= 200 && id <= 299) { 
    iconUrl = icons3D.thunderstorm;
    bgClass = 'bg-slate-900 border-yellow-500 text-white';
  } 
  else if (id >= 300 && id <= 599) { 
    iconUrl = (props.isDay && id === 500) ? icons3D.sunRain : icons3D.rain;
    bgClass = 'bg-blue-600 border-blue-400 text-white';
  } 
  else if (id >= 802 && id <= 804) { 
    iconUrl = icons3D.cloudy;
    bgClass = id >= 803 ? 'bg-slate-500 border-slate-400 text-white' : 'bg-blue-100 border-white';
  }
  else if (id >= 701 && id <= 781) { 
    iconUrl = icons3D.fog;
    bgClass = 'bg-gray-300 border-gray-200';
  }
  else if (!props.isDay && (id === 800 || icon === '01n')) { 
    iconUrl = icons3D.moon;
    bgClass = 'bg-slate-800 border-slate-600 text-white';
  }
  else if (id === 801 && !props.isDay) { 
    iconUrl = icons3D.cloudy; 
    bgClass = 'bg-slate-700 border-slate-500 text-white';
  }
  else if (id === 801 && props.isDay) { 
    iconUrl = icons3D.sunCloud;
    bgClass = 'bg-blue-100 border-white';
  }
  else { 
    iconUrl = icons3D.sun;
    bgClass = 'bg-gradient-to-br from-sky-400 to-sky-100 border-white';
  }

  // --- 1. O ROSTINHO DE TEMPERATURA (Animado) ---
  let facePath = 'Smiling%20Face%20with%20Sunglasses.png'; 
  let faceClass = 'border-green-300';
  const timeStamp = new Date().getTime(); // Cache Buster

  if (temp < 10) {
    facePath = 'Cold%20Face.png';
    faceClass = 'border-blue-200 hover:animate-bounce';
  } else if (temp >= 30) {
    facePath = 'Hot%20Face.png';
    faceClass = 'border-orange-400 animate-pulse';
  }

  const faceBadgeUrl = `${animatedFluentEmojiBase}/${facePath}?t=${timeStamp}`;

  const faceBadgeHtml = `
    <div class="absolute -top-2 -right-2 w-8 h-8 bg-white/95 backdrop-blur-md rounded-full shadow-lg border-2 ${faceClass} flex items-center justify-center z-30">
      <img src="${faceBadgeUrl}" onerror="this.onerror=null; this.style.display='none';" class="w-6 h-6 object-contain filter drop-shadow-sm">
    </div>`;

  // --- 2. O BALÃO DE SUGESTÃO FLUTUANDO ACIMA DO ROSTINHO (RESTAURADO ANIMADO ☔) ---
  let suggestionBadgeHtml = '';
  if (id >= 200 && id <= 599) { // Se for tempestade ou chuva, sugere guarda-chuva
    // AQUI OCORREU A MUDANÇA: Usando a URL da fonte animada oficial chovendo!
    const umbrellaUrl = `${animatedWeatherBase}/Umbrella%20with%20Rain%20Drops.png`;
    // Note o '-top-10': ele empurra o balãozinho para ficar exatamente em cima da cabeça do rostinho! animate-bounce dá o toque final.
    suggestionBadgeHtml = `
      <div class="absolute -top-10 -right-1 w-7 h-7 bg-white/95 backdrop-blur-md rounded-full shadow-md border-2 border-blue-400 flex items-center justify-center z-40 animate-bounce">
        <img src="${umbrellaUrl}?t=${timeStamp}" onerror="this.onerror=null; this.style.display='none'" class="w-5 h-5 object-contain">
      </div>`;
  }

  const html = `
    <div class="marker-wrapper">
      <div class="marker-card ${bgClass} ${isMain ? 'marker-main' : ''}" style="background-color: white !important;">
        <img src="${iconUrl}" onerror="this.onerror=null; this.src='${owIconUrl}'; this.style.width='40px';" class="marker-icon">
        ${faceBadgeHtml}
        ${suggestionBadgeHtml}
      </div>
      <div class="marker-label">${Math.round(temp)}°C</div>
    </div>`;

  const iconObj = L.divIcon({ className: 'custom-leaflet-icon', html: html, iconSize: [60, 90], iconAnchor: [30, 75] });
  
  const marker = L.marker([lat, lon], { icon: iconObj }).addTo(markersGroup);

  let uvBadge = '';
  if (isMain && props.uv !== undefined) {
    let uvColor = 'bg-green-500';
    if (props.uv >= 3) uvColor = 'bg-yellow-500';
    if (props.uv >= 6) uvColor = 'bg-orange-500';
    if (props.uv >= 8) uvColor = 'bg-red-500';
    if (props.uv >= 11) uvColor = 'bg-purple-500';
    uvBadge = `<div class="mt-1 flex justify-center gap-1"><span class="w-2 h-2 rounded-full ${uvColor}"></span><span class="text-[9px] font-bold text-slate-500">UV ${Math.round(props.uv)}</span></div>`;
  }
  
  marker.bindPopup(`<div class="text-center font-bold text-slate-800 p-1 text-lg">${Math.round(temp)}°C${uvBadge}</div>`, { closeButton: false });

  marker.on('click', (e) => {
    L.DomEvent.stopPropagation(e);
    map.flyTo([lat, lon], 17);
    marker.openPopup();
    emit('mapClick', { lat, lon });
  });
};

const renderMapData = () => {
  if (!map || !markersGroup) return;
  markersGroup.clearLayers();

  createCustomMarker(props.lat, props.lon, props.temp, props.iconCode, props.weatherId, true);

  if (props.nearby) {
    props.nearby.forEach(city => {
      // ANTI-FANTASMA (Evita desenhar pino duplicado na mesma posição)
      if (Math.abs(city.coord.lat - props.lat) < 0.005 && Math.abs(city.coord.lon - props.lon) < 0.005) {
        return; // Ignora se for a mesma cidade do pino principal
      }

      createCustomMarker(
        city.coord.lat, 
        city.coord.lon, 
        city.main.temp, 
        city.weather[0].icon, 
        city.weather[0].id, 
        false
      );
    });
  }
};

onMounted(() => {
  map = L.map('mapContainer', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 16);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
  markersGroup = L.layerGroup().addTo(map);
  
  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    map.flyTo([lat, lng], 16);
    emit('mapClick', { lat: lat, lon: lng });
  });

  setTimeout(() => {
    renderMapData();
    map.invalidateSize();
  }, 600);
});

const centralizarMapa = () => map?.flyTo([props.lat, props.lon], 17);
const trocarCamada = (id) => { camadaAtiva.value = id; };

watch(() => [props.lat, props.lon, props.nearby, props.weatherId], ([newLat, newLon]) => {
  if (map) {
    map.flyTo([newLat, newLon], 16);
    renderMapData();
  }
});
</script>

<style>
.custom-leaflet-icon {
  background: none !important;
  border: none !important;
}

.custom-leaflet-icon * {
  filter: none !important;
  opacity: 1 !important;
}

.marker-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.marker-card {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border-width: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  background-size: cover;
  position: relative;
  background-color: white !important; 
}

.marker-main {
  border-color: #3b82f6 !important;
  transform: scale(1.15);
  z-index: 999;
}

.marker-icon {
  width: 35px;
  height: 35px;
  object-fit: contain;
}

.marker-label {
  margin-top: 4px;
  background: #1e293b;
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 10px;
  white-space: nowrap;
}

.controles-container { display: flex; flex-direction: row; gap: 10px; padding: 10px; justify-content: center; align-items: center; }
.botao-controle {
  width: 48px; height: 48px; border-radius: 1rem; display: flex;
  align-items: center; justify-content: center; transition: all 0.3s;
  border-width: 2px; position: relative; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
@media (min-width: 768px) {
  .controles-container { flex-direction: column; padding-right: 30px; height: 100%; position: relative; z-index: 100; }
}
.tooltip {
  position: absolute; right: 120%; background: #0f172a; color: white;
  font-size: 10px; padding: 5px 10px; border-radius: 6px; opacity: 0;
  pointer-events: none; transition: opacity 0.2s; white-space: nowrap;
}
.botao-controle:hover .tooltip { opacity: 1; }
</style>