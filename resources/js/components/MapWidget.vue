<template>
  <div class="w-full h-full flex flex-col md:flex-row gap-4 overflow-hidden">

    <div class="flex-1 rounded-[2.5rem] overflow-hidden shadow-2xl border-4 transition-all duration-500 relative z-0"
      :class="isDark ? 'border-slate-800 shadow-blue-900/10' : 'border-white shadow-slate-200'">
      <div id="mapContainer" class="w-full h-full outline-none bg-slate-200 dark:bg-slate-900"></div>
    </div>

    <div class="controles-container">
      <button v-for="camada in camadasDisponiveis" :key="camada.id" @click="trocarCamada(camada.id)"
        class="w-12 h-12 rounded-2xl transition-all duration-300 flex items-center justify-center shadow-lg border-2 group/btn relative"
        :class="[
          // Se estiver ativo: Borda Azul + Fundo levemente azulado
          camadaAtiva === camada.id 
            ? 'border-blue-500 bg-blue-50 dark:bg-slate-700 shadow-blue-500/30 scale-110 ring-2 ring-blue-400/50' 
            // Se estiver inativo: Borda suave + Fundo Branco (ou Escuro)
            : 'border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800 hover:scale-105 hover:border-blue-300 dark:hover:border-slate-500'
        ]">

        <img :src="camada.img" class="w-8 h-8 object-contain filter drop-shadow-sm transition-transform group-hover/btn:scale-110" alt="Icone">

        <span class="
          absolute right-full mr-4 px-3 py-1.5 rounded-xl 
          bg-slate-900 text-white text-[10px] font-bold uppercase tracking-wider
          opacity-0 group-hover/btn:opacity-100 transition-opacity duration-200 
          pointer-events-none whitespace-nowrap shadow-xl z-50 min-w-max hidden md:block
        ">
          {{ camada.nome }}
          <span class="absolute top-1/2 -right-1.5 -mt-1 w-2.5 h-2.5 bg-slate-900 rotate-45"></span>
        </span>
      </button>
    </div>

    <div class="hidden" style="display: none;">
       <img src="https://em-content.zobj.net/source/microsoft-teams/337/cloud-with-rain_1f327.png">
       <img src="https://em-content.zobj.net/source/microsoft-teams/337/snowflake_2744-fe0f.png">
    </div>

  </div>
</template>

<script setup>
import { onMounted, watch, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Adicionei 'uv' aqui para receber o dado do Pai
const props = defineProps([
  'lat', 'lon', 'temp', 'iconCode', 'weatherId', 'timezone', 'nearby', 'isDark', 'isDay', 'uv'
]);
const emit = defineEmits(['mapClick']);

let map = null;
let tileLayer = null;
let weatherLayer = null;
let markersGroup = null;

// --- LISTA DE CAMADAS COMPLETA ---
// URLs das Imagens 3D
const imgBase = 'https://em-content.zobj.net/source/microsoft-teams/337';

// --- LISTA DE CAMADAS COM ÍCONES 3D (CDN GitHub - Blindado 🛡️) ---
const baseCdn = 'https://cdn.jsdelivr.net/gh/microsoft/fluentui-emoji@main/assets';

const camadasDisponiveis = [
  { id: null, nome: 'Limpo', img: `${baseCdn}/Cross%20mark/3D/cross_mark_3d.png` },
  
  // AQUI: Voltei com o GUARDA-CHUVA 3D para o botão! ☔
  { id: 'precipitation_new', nome: 'Chuva', img: `${baseCdn}/Umbrella%20with%20rain%20drops/3D/umbrella_with_rain_drops_3d.png` },
  
  { id: 'snow_new', nome: 'Neve', img: `${baseCdn}/Snowflake/3D/snowflake_3d.png` },
  { id: 'clouds_new', nome: 'Nuvens', img: `${baseCdn}/Cloud/3D/cloud_3d.png` },
  { id: 'temp_new', nome: 'Calor', img: `${baseCdn}/Thermometer/3D/thermometer_3d.png` },
  { id: 'pressure_new', nome: 'Pressão', img: `${baseCdn}/Stopwatch/3D/stopwatch_3d.png` },
  { id: 'wind_new', nome: 'Vento', img: `${baseCdn}/Wind%20face/3D/wind_face_3d.png` }
];
const camadaAtiva = ref(null);

const tileUrls = {
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
  weatherLayer = L.tileLayer(urlProxy, { opacity: 0.8, zIndex: 10 }).addTo(map);
};

const updateTiles = () => {
  if (tileLayer) tileLayer.setUrl(props.isDark ? tileUrls.dark : tileUrls.light);
};

const createCustomMarker = (lat, lon, temp, icon, isMain = false) => {
  
  // 1. URL DE SEGURANÇA
  const fallbackIcon = `https://openweathermap.org/img/wn/${icon}@2x.png`;

  // 2. BASE CDN BLINDADA (GitHub Microsoft Oficial) 🛡️
  const baseCdn = 'https://cdn.jsdelivr.net/gh/microsoft/fluentui-emoji@main/assets';
  const baseFace = 'https://raw.githubusercontent.com/Tarikul-Islam-Anik/Animated-Fluent-Emojis/master/Emojis/Smilies';

  // 3. CONFIGURAÇÃO VISUAL
  // Padrão: Sol
  let iconUrl = `${baseCdn}/Sun/3D/sun_3d.png`;
  let bgClass = 'bg-gradient-to-b from-blue-100 to-white border-white'; 
  let imgFilter = '';

  // --- SOL (01d) ☀️ ---
  if (icon === '01d') {
     iconUrl = `${baseCdn}/Sun/3D/sun_3d.png`;
     bgClass = 'bg-gradient-to-br from-sky-400 to-sky-200 border-white ring-2 ring-sky-100'; 
  }
  // --- LUA (01n) 🌕 ---
  else if (icon === '01n') {
     iconUrl = `${baseCdn}/Full%20moon/3D/full_moon_3d.png`;
     bgClass = 'bg-slate-800 border-slate-600';
  }
  // --- SOL COM NUVEM (02d) 🌤️ ---
  else if (icon === '02d') {
     iconUrl = `${baseCdn}/Sun%20behind%20cloud/3D/sun_behind_cloud_3d.png`;
     bgClass = 'bg-gradient-to-br from-blue-400 to-blue-100';
  }
  // --- NUVENS (03, 04) ☁️ ---
  else if (icon.includes('03') || icon.includes('04') || icon === '02n') {
     iconUrl = `${baseCdn}/Cloud/3D/cloud_3d.png`;
     bgClass = 'bg-gradient-to-br from-blue-500 to-blue-300 border-blue-200';
  }
  // --- CHUVA (09, 10) 🌧️ ---
  else if (icon.includes('09') || icon.includes('10')) {
     // AQUI: Nuvem com Chuva para o MAPA
     iconUrl = `${baseCdn}/Cloud%20with%20rain/3D/cloud_with_rain_3d.png`; 
     
     if (icon === '10d') iconUrl = `${baseCdn}/Sun%20behind%20rain%20cloud/3D/sun_behind_rain_cloud_3d.png`;
     
     bgClass = 'bg-gradient-to-br from-blue-600 to-blue-500 border-blue-400';
  }
  // --- RAIO (11) ⛈️ ---
  else if (icon.includes('11')) {
     iconUrl = `${baseCdn}/Cloud%20with%20lightning/3D/cloud_with_lightning_3d.png`;
     bgClass = 'bg-slate-700 border-yellow-400';
  }
  // --- NEVE (13) ❄️ ---
  else if (icon.includes('13')) {
     iconUrl = `${baseCdn}/Snowflake/3D/snowflake_3d.png`;
     bgClass = 'bg-blue-500 border-white ring-2 ring-blue-300';
     imgFilter = 'brightness-0 invert'; // Branco Puro
  }
  // --- NEBLINA (50) 🌫️ ---
  else if (icon.includes('50')) {
     iconUrl = `${baseCdn}/Fog/3D/fog_3d.png`;
     bgClass = 'bg-gray-400 border-gray-300';
  }

  // 4. REAÇÃO (Rostinhos - Mantido CDN Rápido)
  let badgeUrl = '';
  let badgeClass = '';
  const timeStamp = new Date().getTime(); // Cache Buster

  if (temp < 10) {
    badgeUrl = `${baseFace}/Cold%20Face.png?t=${timeStamp}`;
    badgeClass = 'border-blue-200 hover:animate-bounce';
  } else if (temp >= 30) {
    badgeUrl = `${baseFace}/Hot%20Face.png?t=${timeStamp}`;
    badgeClass = 'border-orange-400 animate-pulse';
  } else {
    badgeUrl = `${baseFace}/Smiling%20Face%20with%20Sunglasses.png?t=${timeStamp}`;
    badgeClass = 'border-green-300';
  }

  const reactionBadgeHtml = `
    <div class="absolute -top-2 -right-2 w-8 h-8 bg-white/95 backdrop-blur-md rounded-full shadow-lg border-2 ${badgeClass} flex items-center justify-center z-30">
      <img src="${badgeUrl}" onerror="this.style.display='none'" class="w-6 h-6 object-contain filter drop-shadow-sm">
    </div>`;

  // 5. HTML FINAL
  const html = `
    <div class="relative flex flex-col items-center justify-center transition-transform hover:scale-110 cursor-pointer group">
      <div class="w-16 h-16 rounded-full shadow-2xl flex items-center justify-center border-[3px] relative z-20 overflow-visible 
                  ${bgClass}
                  ${isMain ? 'shadow-blue-600/50 scale-110' : 'shadow-slate-600/30'}">
        <img src="${iconUrl}" 
             onerror="this.onerror=null; this.src='${fallbackIcon}';"
             class="w-11 h-11 object-contain filter drop-shadow-md transform transition-transform ${imgFilter}" 
             alt="Clima">
        ${reactionBadgeHtml}
      </div>
      <div class="w-4 h-4 rotate-45 -mt-2 shadow-sm relative z-10 border-b-2 border-r-2 rounded-br bg-white border-slate-300">
      </div>
      <div class="absolute -bottom-10 bg-slate-900 text-white text-[12px] font-bold px-3 py-1 rounded-full shadow-lg border border-slate-700 z-30 whitespace-nowrap">
        ${Math.round(temp)}°C
      </div>
    </div>
  `;

  const iconObj = L.divIcon({
    className: 'custom-pin-container',
    html: html,
    iconSize: [64, 100], 
    iconAnchor: [32, 60],
    popupAnchor: [0, -65]
  });

  const marker = L.marker([lat, lon], { icon: iconObj }).addTo(markersGroup);

  marker.on('click', (e) => {
    L.DomEvent.stopPropagation(e);
    map.flyTo([lat, lon], 17);
    marker.openPopup();
    emit('mapClick', { lat, lon });
  });

  // Popup com UV
  let uvBadge = '';
  if (isMain && props.uv !== undefined) {
    let uvColor = 'bg-green-500';
    let uvText = 'Baixo';
    if (props.uv >= 3) { uvColor = 'bg-yellow-500'; uvText = 'Mod.'; }
    if (props.uv >= 6) { uvColor = 'bg-orange-500'; uvText = 'Alto'; }
    if (props.uv >= 8) { uvColor = 'bg-red-500'; uvText = 'M.Alto'; }
    if (props.uv >= 11) { uvColor = 'bg-purple-500'; uvText = 'Extr.'; }
    uvBadge = `<div class="mt-1 flex justify-center gap-1"><span class="w-2 h-2 rounded-full ${uvColor}"></span><span class="text-[9px] font-bold text-slate-500">UV ${Math.round(props.uv)}</span></div>`;
  }
  marker.bindPopup(`<div class="text-center font-bold text-slate-800 p-1 text-lg">${Math.round(temp)}°C${uvBadge}</div>`, { closeButton: false });
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
  map = L.map('mapContainer', { zoomControl: false, attributionControl: false }).setView([props.lat, props.lon], 16);
  const urlInicial = props.isDark ? tileUrls.dark : tileUrls.light;
  tileLayer = L.tileLayer(urlInicial, { maxZoom: 19 }).addTo(map);
  markersGroup = L.layerGroup().addTo(map);
  map.on('click', (e) => {
    const { lat, lng } = e.latlng;
    map.flyTo([lat, lng], 17);
    emit('mapClick', { lat: lat, lon: lng });
  });
  renderMapData();
});

watch(() => props.isDark, updateTiles);
watch(() => [props.lat, props.lon, props.isDay, props.uv], ([lat, lon]) => {
  if (map) {
    map.flyTo([lat, lon], 17);
    renderMapData();
  }
});
</script>

<style>
/* --- ANIMAÇÃO DO SOL (Opcional, mas recomendado) --- */
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 12s linear infinite;
}

#mapContainer {
  background: transparent;
  z-index: 1;
}
.leaflet-div-icon {
  background: transparent;
  border: none;
}
.leaflet-container:focus {
  outline: none;
}

/* --- ESTILO DOS BOTÕES --- */
.controles-container {
  /* MOBILE */
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  gap: 8px; /* Diminui um pouco o gap para caber os botões novos no mobile */
  width: 100%;
  padding-bottom: 5px;
  flex-wrap: wrap; /* Permite quebrar linha se não couber no celular */
}

/* PC */
@media (min-width: 768px) {
  .controles-container {
    flex-direction: column;
    justify-content: center;
    width: auto;
    height: 100%;
    padding-bottom: 0;
    gap: 12px;
    flex-wrap: nowrap;
  }
}
</style>