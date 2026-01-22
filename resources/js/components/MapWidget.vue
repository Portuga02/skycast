<template>
  <div class="w-full mt-10 bg-white p-2 sm:p-4 rounded-t-[3rem] shadow-2xl z-0 relative transition-all duration-700">
    <div id="mapContainer" class="h-[500px] w-full rounded-t-[2.5rem] z-0 border-t border-slate-100"></div>
  </div>
</template>


<script setup>
import { onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Recebe as coordenadas vindas da busca do clima
const props = defineProps({
  lat: Number,
  lon: Number
});

let map = null;
let marker = null;

onMounted(() => {
  iniciarMapa();
});

// Watcher para atualizar a posição se o usuário buscar outra cidade
watch(() => [props.lat, props.lon], () => {
  if (map) {
    atualizarMapa();
  } else {
    iniciarMapa();
  }
});

const iniciarMapa = () => {
  if (!props.lat || !props.lon) return;

  // Configuração inicial com zoom 13 (ideal para cidades)
  map = L.map('mapContainer', {
    zoomControl: false,
    attributionControl: false
  }).setView([props.lat, props.lon], 13);

  // TileLayer: Usando o estilo 'Voyager' que é mais clean e moderno
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    maxZoom: 19
  }).addTo(map);

  adicionarMarcador();
};

const atualizarMapa = () => {
  map.setView([props.lat, props.lon], 13);
  adicionarMarcador();
};

const adicionarMarcador = () => {
  if (marker) map.removeLayer(marker);

  // Marcador estilizado em azul para combinar com a marca SKYCAST
  marker = L.circleMarker([props.lat, props.lon], {
    radius: 10,
    fillColor: "#3b82f6",
    color: "#fff",
    weight: 3,
    opacity: 1,
    fillOpacity: 0.9
  }).addTo(map);
};
</script>

<style scoped>
#mapContainer {
  z-index: 1; /* Garante que o mapa fique abaixo de dropdowns de busca */
}
</style>