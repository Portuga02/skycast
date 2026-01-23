<template>
  <div :class="isDark ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900'"
       class="min-h-screen flex flex-col items-center font-sans pb-20 transition-colors duration-500 overflow-x-hidden">

    <button @click="toggleDarkMode" 
      class="fixed bottom-6 right-6 z-[2000] p-4 rounded-full shadow-2xl transition-all hover:scale-110 active:scale-95 border group"
      :class="isDark ? 'bg-yellow-400 border-yellow-500' : 'bg-slate-900 border-slate-700'">
      <span v-if="isDark" class="text-xl">☀️</span>
      <span v-else class="text-xl">🌙</span>
    </button>

    <div class="w-full max-w-6xl px-6 pt-10 flex flex-col items-center">
      <header class="w-full flex justify-between items-center mb-8">
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-black text-blue-600 tracking-tighter uppercase">
            SKYCAST <span :class="isDark ? 'text-slate-600' : 'text-slate-400'" class="font-light italic">PRO</span>
          </h1>
        </div>
        <div class="text-right hidden sm:block">
          <p class="text-[10px] text-blue-500 uppercase tracking-widest font-bold italic">Software Engineer</p>
          <p class="text-xs text-slate-400 font-medium italic">Laravel + Vue.js + OpenWeather</p>
        </div>
      </header>

      <div class="w-full max-w-md relative mb-10 z-50">
        <div class="flex gap-3">
          <div class="relative flex-1">
            <input v-model="cidadeInput" @input="buscarSugestoes" @keyup.enter="executarBuscaFinal(cidadeInput)"
              type="text" placeholder="Digite a cidade ou use o GPS..."
              class="w-full pl-6 pr-16 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all bg-white dark:bg-slate-900 dark:text-white truncate" />
            
            <button @click="usarLocalizacao" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 p-2 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
            </button>

            <ul v-if="sugestoes.length > 0"
              class="absolute z-50 w-full bg-white dark:bg-slate-900 mt-2 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
              <li v-for="(cidade, index) in sugestoes" :key="index" @mousedown.prevent="selecionarCidade(cidade)"
                class="px-6 py-4 hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer flex justify-between items-center border-b border-slate-50 dark:border-slate-800 last:border-none">
                <div class="flex flex-col">
                  <span class="font-bold text-slate-700 dark:text-slate-200">{{ cidade.name }}</span>
                  <span class="text-[10px] text-slate-400 tracking-wider">{{ cidade.country }}</span>
                </div>
                <span class="text-[10px] font-black bg-blue-100 text-blue-600 px-2 py-1 rounded">{{ cidade.state || 'UF' }}</span>
              </li>
            </ul>
          </div>
          <button @click="executarBuscaFinal(cidadeInput)" :disabled="carregando"
            class="bg-blue-600 text-white px-8 py-4 rounded-2xl shadow-lg font-bold hover:bg-blue-700 transition-all disabled:opacity-50">
            {{ carregando ? '...' : 'BUSCAR' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="dadosClima" class="w-full max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-8 items-start z-0 mb-12">
      
      <transition name="fade-slide" appear>
        <div class="w-full bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl relative overflow-hidden min-h-[420px] flex flex-col justify-center">
          <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>
          <div class="relative z-10 text-center">
            <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-2 italic">{{ dadosClima.weather[0].description }}</p>
            <div v-if="dadosClima.air_quality" class="mb-4 flex justify-center">
              <span :class="getAirQualityInfo(dadosClima.air_quality).color" class="px-3 py-1 rounded-full text-[10px] font-black uppercase text-white shadow-lg flex items-center gap-2">
                {{ getAirQualityInfo(dadosClima.air_quality).emoji }} Ar: {{ getAirQualityInfo(dadosClima.air_quality).text }}
              </span>
            </div>
            <h2 class="text-4xl font-extrabold mb-6 tracking-tight">
              {{ nomeExibicao || dadosClima.name }}
              <span class="text-slate-500 italic font-medium block text-xl mt-1">{{ dadosClima.state ? dadosClima.state + ' - BR' : 'BR' }}</span>
            </h2>
            <div class="flex items-center justify-center gap-6 mb-4">
              <span class="text-8xl font-black tracking-tighter">{{ Math.round(dadosClima.main.temp) }}°</span>
              <span class="text-6xl drop-shadow-2xl">{{ obterEmojiSimples(dadosClima.main.temp) }}</span>
            </div>
            <div class="flex justify-center gap-8 mt-6 pt-6 border-t border-slate-800 text-slate-400 text-xs uppercase font-bold tracking-widest">
              <span>💧 {{ dadosClima.main.humidity }}%</span>
              <span>🌬️ {{ Math.round(dadosClima.wind.speed) }} km/h</span>
            </div>
          </div>
        </div>
      </transition>

      <div :class="isDark ? 'border-slate-800 shadow-blue-900/10' : 'border-white shadow-slate-200'" 
           class="w-full h-[420px] rounded-[2.5rem] overflow-hidden shadow-2xl border-4 relative z-0 bg-slate-200 transition-all duration-500">
          <MapWidget v-if="dadosClima && dadosClima.coord" :lat="dadosClima.coord.lat" :lon="dadosClima.coord.lon" :temp="dadosClima.main.temp" :icon-code="dadosClima.weather[0].icon" :weather-id="dadosClima.weather[0].id" :temp-min="dadosClima.main.temp_min" :temp-max="dadosClima.main.temp_max" :timezone="dadosClima.timezone" :nearby="dadosClima.nearby" @mapClick="handleMapClick" />
      </div>
    </div>

    <div v-if="previsaoHoraria.length > 0" class="w-full max-w-6xl px-6 mb-12 relative z-20">
      <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 pl-2">Nas Próximas 24 Horas</h3>
      <div class="flex gap-4 overflow-x-auto pb-4 snap-x no-scrollbar">
        <div v-for="hora in previsaoHoraria" :key="hora.dt"
          :class="isDark ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-slate-100 shadow-slate-200/50'"
          class="min-w-[125px] snap-start p-6 rounded-[2.5rem] border shadow-xl flex flex-col items-center justify-between transition-all hover:scale-105 group">
          <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
            {{ new Date(hora.dt * 1000).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}
          </span>
          <div class="text-3xl mb-3 drop-shadow-md group-hover:scale-110 transition-transform">
            {{ obterEmojiSimples(hora.main.temp) }}
          </div>
          <span :class="isDark ? 'text-white' : 'text-slate-700'" class="text-2xl font-black tracking-tighter">{{ Math.round(hora.main.temp) }}°</span>
          <div v-if="hora.pop > 0" 
            :class="isDark ? 'bg-blue-900/50 text-blue-300' : 'bg-blue-50 text-blue-600'"
            class="mt-3 flex items-center gap-1 text-[9px] font-black px-2 py-1 rounded-full">
            <span class="animate-pulse">💧</span> {{ Math.round(hora.pop * 100) }}%
          </div>
        </div>
      </div>
    </div>

    <div v-if="previsaoSemana.length > 0" class="w-full max-w-6xl px-6 z-20">
      <div class="flex items-center gap-4 mb-6">
        <div :class="isDark ? 'bg-slate-800' : 'bg-slate-200'" class="h-px flex-1"></div>
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Previsão Semanal</h3>
        <div :class="isDark ? 'bg-slate-800' : 'bg-slate-200'" class="h-px flex-1"></div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div v-for="dia in previsaoSemana" :key="dia.dt"
          :class="isDark ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-100'"
          class="p-8 rounded-[2.5rem] shadow-lg border flex flex-col items-center text-center transition-all duration-300 hover:shadow-2xl">
          <span class="text-[10px] font-black text-blue-500 uppercase mb-4">{{ new Date(dia.dt * 1000).toLocaleDateString('pt-BR', { weekday: 'short' }) }}</span>
          <span class="text-4xl mb-3">{{ obterEmojiSimples(dia.main.temp) }}</span>
          <span :class="isDark ? 'text-white' : 'text-slate-800'" class="text-2xl font-black">{{ Math.round(dia.main.temp) }}°</span>
          <span class="text-[9px] text-slate-400 font-bold uppercase mt-3 leading-tight">{{ dia.weather[0].description }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import MapWidget from './components/MapWidget.vue';

// --- ESTADOS ---
const cidadeInput = ref('');
const sugestoes = ref([]);
const dadosClima = ref(null);
const nomeExibicao = ref(null);
const carregando = ref(false);
const previsaoSemana = ref([]);
const previsaoHoraria = ref([]);
const isDark = ref(false);

// --- TEMA ---
const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
};

onMounted(() => {
  const saved = localStorage.getItem('theme');
  if (saved === 'dark') {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }
});

// --- BUSCAS ---
const buscarSugestoes = async () => {
  if (cidadeInput.value.length < 3) { sugestoes.value = []; return; }
  try {
    const res = await axios.get(`/api/cidades/busca/${encodeURIComponent(cidadeInput.value)}`);
    sugestoes.value = res.data;
  } catch (e) { console.error("Erro sugestões"); }
};

const selecionarCidade = (c) => {
  nomeExibicao.value = c.name;
  executarBuscaFinal(`${c.name} - ${c.state || ''}, ${c.country}`);
  sugestoes.value = [];
};

const executarBuscaFinal = async (t) => {
  if (!t) return;
  carregando.value = true;
  try {
    const res = await axios.get(`/api/clima/${encodeURIComponent(t.replace(/\s*-\s*/, '-'))}`);
    if (res.data && res.data.list) {
      processarRespostaClima(res.data);
      cidadeInput.value = '';
    }
  } catch (e) { console.error("Erro busca"); } finally { carregando.value = false; }
};

const handleMapClick = async (coords) => {
  carregando.value = true;
  try {
    const res = await axios.get(`/api/clima/coordenadas`, { params: { lat: coords.lat, lon: coords.lon } });
    if (res.data && res.data.list) {
      nomeExibicao.value = null;
      processarRespostaClima(res.data);
    }
  } catch (e) { console.error("Erro mapa"); } finally { carregando.value = false; }
};

const usarLocalizacao = () => {
  if (!navigator.geolocation) return alert("Sem GPS");
  carregando.value = true;
  navigator.geolocation.getCurrentPosition(async (pos) => {
    handleMapClick({ lat: pos.coords.latitude, lon: pos.coords.longitude });
  }, () => { carregando.value = false; });
};

const processarRespostaClima = (d) => {
  const atual = d.list[0];
  dadosClima.value = { ...atual, name: d.city.name, coord: d.city.coord, state: d.city.state_uf || '', air_quality: d.air_quality || null, nearby: d.nearby || [] };
  previsaoHoraria.value = d.list.slice(0, 8);
  previsaoSemana.value = d.list.filter(i => i.dt_txt.includes("12:00:00"));
};

const obterEmojiSimples = (t) => {
  if (t > 30) return '🔥';
  if (t > 22) return '☀️';
  if (t > 15) return '☁️';
  return '❄️';
};

const getAirQualityInfo = (aqi) => {
  const infos = { 
    1: { text: 'Excelente', color: 'bg-green-500', emoji: '🍃' }, 
    2: { text: 'Boa', color: 'bg-lime-500', emoji: '😊' }, 
    3: { text: 'Moderada', color: 'bg-yellow-500', emoji: '😐' }, 
    4: { text: 'Ruim', color: 'bg-orange-500', emoji: '😷' }, 
    5: { text: 'Péssima', color: 'bg-red-600', emoji: '☠️' } 
  };
  return infos[aqi] || { text: 'N/A', color: 'bg-slate-500', emoji: '❓' };
};
</script>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.fade-slide-enter-active { transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-slide-enter-from { opacity: 0; transform: translateY(20px); }
.leaflet-container { z-index: 10 !important; }
</style>