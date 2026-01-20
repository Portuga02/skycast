<template>
  <div class="w-full h-[400px] shadow-inner border-y border-slate-200 bg-slate-100 relative z-0">
    <div id="map" class="w-full h-full"></div>
  </div>
</template>

<script setup>
import { onMounted, watch, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Importando as imagens do marcador para garantir que o Leaflet as encontre
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const props = defineProps(['lat', 'lon']);
let map = null;
let marker = null;

// Configuração do ícone padrão (o "pingo" vermelho)
const defaultIcon = L.icon({
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
});

const initMap = () => {
  if (map) {
    map.remove();
  }

  map = L.map('map', {
    zoomControl: false,
    attributionControl: false
  }).setView([props.lat, props.lon], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  // Criando o marcador com o ícone de Pin
  marker = L.marker([props.lat, props.lon], { icon: defaultIcon }).addTo(map);
};

onMounted(() => {
  initMap();
});

watch(() => [props.lat, props.lon], () => {
  if (map) {
    map.setView([props.lat, props.lon], 12);
    if (marker) {
      marker.setLatLng([props.lat, props.lon]);
    }
  }
});
</script>

<style scoped>
/* Garante que o mapa não quebre o layout */
#map {
  width: 100%;
  height: 100%;
}
</style>