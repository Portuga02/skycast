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

        <div class="flex gap-2 sm:gap-3">
          <button @click="usarLocalizacao" title="Usar localização atual"
            class="px-4 py-4 rounded-2xl shadow-xl font-bold transition-all hover:scale-105 active:scale-95 flex items-center justify-center group border-2 border-transparent"
            :class="isDark
              ? 'bg-slate-800 text-blue-400 hover:border-blue-500 shadow-blue-900/20'
              : 'bg-white text-blue-600 hover:border-blue-200 shadow-slate-200'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
              stroke="currentColor" class="w-6 h-6 group-hover:animate-bounce">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
          </button>

          <div class="relative flex-1">
            <input v-model="cidadeInput" @input="buscarSugestoes" @keyup.enter="executarBuscaFinal(cidadeInput)"
              type="text" placeholder="Digite a cidade..."
              class="w-full pl-6 pr-6 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all truncate outline-none"
              :class="isDark
                ? 'bg-slate-900 text-white placeholder-slate-500 shadow-blue-900/20'
                : 'bg-white text-slate-900 placeholder-slate-400 shadow-slate-200'" />

            <ul v-if="sugestoes.length > 0"
              class="absolute z-[101] w-full mt-2 rounded-2xl shadow-2xl overflow-hidden border" :class="isDark
                ? 'bg-slate-900 text-white border-slate-800'
                : 'bg-white text-slate-900 border-slate-200'">
              <li v-for="(cidade, index) in sugestoes" :key="index" @mousedown.prevent="selecionarCidade(cidade)"
                class="px-6 py-4 cursor-pointer flex justify-between items-center transition-colors border-b last:border-none"
                :class="isDark
                  ? 'hover:bg-slate-800 border-slate-800'
                  : 'hover:bg-slate-50 border-slate-100'">
                <div class="flex flex-col text-left">
                  <span class="font-bold" :class="isDark ? 'text-slate-100' : 'text-slate-800'">
                    {{ cidade.name }}
                  </span>
                  <span class="text-[10px] tracking-wider uppercase"
                    :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                    {{ cidade.country }}
                  </span>
                </div>
                <span class="text-[10px] font-black px-2 py-1 rounded" :class="isDark
                  ? 'bg-blue-900/50 text-blue-300'
                  : 'bg-blue-100 text-blue-700'">
                  {{ cidade.state || 'UF' }}
                </span>
              </li>
            </ul>
          </div>


          <button @click="executarBuscaFinal(cidadeInput)" :disabled="carregando"
            class="px-6 sm:px-8 py-4 rounded-2xl shadow-xl font-bold transition-all disabled:opacity-50 bg-blue-600 text-white hover:bg-blue-700 hover:shadow-blue-600/40 active:scale-95">
            <span class="hidden sm:inline">{{ carregando ? '...' : 'BUSCAR' }}</span>
            <span class="sm:hidden">🔍</span>
          </button>

        </div>
      </div>
    </div>

    <div v-if="dadosClima" class="w-full max-w-6xl px-6 grid grid-cols-1 lg:grid-cols-2 gap-8 items-start z-0 mb-8">

      <transition name="fade-slide" appear>
        <div
          class="w-full bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl relative overflow-hidden min-h-[420px] flex flex-col justify-center group">

          <div
            class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-[100px] transition-all duration-1000 opacity-60"
            :class="verificarSeEhDia(dadosClima) ? 'bg-blue-400' : 'bg-indigo-600'">
          </div>

          <div class="relative z-10 text-center">

            <div class="flex justify-center mb-4">
              <div
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/10 bg-white/5 backdrop-blur-md shadow-lg">
                <span class="text-xs font-mono tracking-widest text-slate-300">
                  {{ obterHoraLocal(dadosClima.timezone) }}
                </span>
                <span class="text-xs animate-pulse">
                  {{ verificarSeEhDia(dadosClima) ? '☀️' : '✨' }}
                </span>
              </div>
            </div>

            <h2 class="text-5xl font-black mb-2 tracking-tighter drop-shadow-lg">
              {{ nomeExibicao || dadosClima.name }}
            </h2>
            <p class="text-lg md:text-xl italic text-blue-200 font-medium mb-8">
              <span v-if="dadosClima.city_original && dadosClima.name !== dadosClima.city_original">
                {{ dadosClima.city_original }} -
              </span>

              <span v-if="dadosClima.country === 'BR'">{{ dadosClima.state }} - BR</span>
              <span v-else>{{ dadosClima.country }}</span>
            </p>

            <div class="flex flex-col items-center justify-center gap-2 mb-6">
              <span class="text-[7rem] leading-none filter drop-shadow-2xl animate-float">
                {{ obterIconeVisual(dadosClima.weather[0].icon, verificarSeEhDia(dadosClima)) }}
              </span>

              <span class="text-8xl font-black tracking-tighter mt-4">
                {{ Math.round(dadosClima.main.temp) }}°
              </span>

              <p class="text-blue-300 font-bold uppercase tracking-[0.3em] text-xs mt-2">
                {{ dadosClima.weather[0].description }}
              </p>
            </div>

            <div
              class="flex justify-center gap-8 pt-6 border-t border-white/10 text-slate-400 text-xs uppercase font-bold tracking-widest">
              <span class="flex items-center gap-2">
                <span class="text-blue-400">💧</span> {{ dadosClima.main.humidity }}%
              </span>
              <span class="flex items-center gap-2">
                <span class="text-blue-400">🌬️</span> {{ Math.round(dadosClima.wind.speed) }} km/h
              </span>
            </div>
          </div>
        </div>
      </transition>

      <div class="w-full h-[570px] relative z-0 mb-12">

        <MapWidget v-if="dadosClima && dadosClima.coord" :lat="dadosClima.coord.lat" :lon="dadosClima.coord.lon"
          :temp="dadosClima.main.temp" :icon-code="dadosClima.weather[0].icon" :weather-id="dadosClima.weather[0].id"
          :timezone="dadosClima.timezone" :nearby="dadosClima.nearby" :is-dark="isDark"
          :is-day="verificarSeEhDia(dadosClima)" :uv="dadosClima.uv" @mapClick="handleMapClick" />

      </div>
    </div>

    <div v-if="dadosClima && previsaoHoraria.length > 0"
      :class="isDark ? 'bg-slate-900/50 border-slate-800' : 'bg-white border-slate-100'"
      class="w-full max-w-6xl px-8 py-8 mb-8 rounded-[2.5rem] border shadow-2xl transition-all">

      <div class="flex justify-between items-center mb-6 pl-2">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
          Tendência de Temperatura (24h)
        </h3>

        <div class="flex gap-2 bg-slate-800/50 p-1 rounded-xl border border-slate-700">
          <button @click="tipoGrafico = 'line'"
            :class="tipoGrafico === 'line' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
            class="px-3 py-1 text-[9px] font-bold rounded-lg transition-all shadow-sm">
            LINHA
          </button>
          <button @click="tipoGrafico = 'bar'"
            :class="tipoGrafico === 'bar' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white'"
            class="px-3 py-1 text-[9px] font-bold rounded-lg transition-all shadow-sm">
            BARRAS
          </button>
        </div>
      </div>

      <TempChart :horas="previsaoHoraria" :is-dark="isDark" :timezone="dadosClima.timezone" :tipo="tipoGrafico" />
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
            {{ obterIconeVisual(hora.weather[0].icon) }}
          </div>
          <span :class="isDark ? 'text-white' : 'text-slate-700'" class="text-2xl font-black tracking-tighter">{{
            Math.round(hora.main.temp) }}°</span>
          <div v-if="hora.pop > 0" :class="isDark ? 'bg-blue-900/50 text-blue-300' : 'bg-blue-50 text-blue-600'"
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
          <span class="text-[10px] font-black text-blue-500 uppercase mb-4">{{ new Date(dia.dt *
            1000).toLocaleDateString('pt-BR', { weekday: 'short' }) }}</span>
          <span class="text-4xl mb-3">{{ obterIconeVisual(dia.weather[0].icon) }}</span>
          <span :class="isDark ? 'text-white' : 'text-slate-800'" class="text-2xl font-black">{{
            Math.round(dia.main.temp) }}°</span>
          <span class="text-[9px] text-slate-400 font-bold uppercase mt-3 leading-tight">{{ dia.weather[0].description
          }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import MapWidget from './components/MapWidget.vue';
import TempChart from './components/TempChart.vue';

// --- ESTADOS ---
const cidadeInput = ref('');
const sugestoes = ref([]);
const dadosClima = ref(null);
const nomeExibicao = ref(null);
const carregando = ref(false);
const previsaoSemana = ref([]);
const previsaoHoraria = ref([]);
const isDark = ref(false);
const tipoGrafico = ref('line');
const estadoSelecionado = ref('');

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

// --- NOVIDADE: VERIFICAR SE É DIA (MATEMÁTICA) ---
const verificarSeEhDia = (dados) => {
  if (!dados || !dados.sys) return true;
  const agora = Math.floor(Date.now() / 1000);
  return agora > dados.sys.sunrise && agora < dados.sys.sunset;
};

// --- TRADUTOR DE ÍCONES ---
// --- TRADUTOR DE ÍCONES INTELIGENTE ---
// Agora aceita um segundo parâmetro opcional: 'forcarDia'
const obterIconeVisual = (iconCode, forcarDia = null) => {
  let codigoFinal = iconCode;

  // Se a matemática disser que é DIA, mas o ícone for NOITE ('n'), a gente troca na marra!
  if (forcarDia === true) {
    codigoFinal = iconCode.replace('n', 'd');
  }
  // Se a matemática disser que é NOITE, mas o ícone for DIA ('d'), troca também.
  else if (forcarDia === false) {
    codigoFinal = iconCode.replace('d', 'n');
  }

  const mapa = {
    // DIA
    '01d': '☀️', '02d': '🌤️', '03d': '☁️', '04d': '☁️',
    '09d': '🌧️', '10d': '🌦️', '11d': '⛈️', '13d': '❄️', '50d': '🌫️',
    // NOITE
    '01n': '🌙',
    '02n': '☁️🌙',
    '03n': '☁️', '04n': '☁️',
    '09n': '🌧️', '10n': '🌧️', '11n': '⛈️', '13n': '❄️', '50n': '🌫️'
  };

  return mapa[codigoFinal] || '🌡️';
};

// --- HORA LOCAL ---
const obterHoraLocal = (offsetSegundos) => {
  const d = new Date();
  const utc = d.getTime() + (d.getTimezoneOffset() * 60000);
  const targetTime = new Date(utc + (1000 * offsetSegundos));
  return targetTime.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

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
  estadoSelecionado.value = c.state;

  if (c.lat && c.lon) {
    handleMapClick({ lat: c.lat, lon: c.lon });
  } else {
    executarBuscaFinal(`${c.name}, ${c.state || ''}, ${c.country}`);
  }

  sugestoes.value = [];
  cidadeInput.value = '';
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

// Substitua a antiga por esta:
const usarLocalizacao = () => {
  // 1. Verifica se o navegador suporta
  if (!navigator.geolocation) {
    alert("Seu navegador não tem suporte a GPS!");
    return;
  }

  carregando.value = true;

  navigator.geolocation.getCurrentPosition(
    // SUCESSO
    (pos) => {
      console.log("GPS Encontrado:", pos.coords.latitude, pos.coords.longitude);
      // Chama a mesma função do mapa
      handleMapClick({ lat: pos.coords.latitude, lon: pos.coords.longitude });
    },
    // ERRO
    (erro) => {
      carregando.value = false;
      console.error("Erro GPS:", erro);

      if (erro.code === 1) {
        alert("🚨 Permissão negada! Clique no cadeado 🔒 na barra de endereço e permita a Localização.");
      } else if (erro.code === 2) {
        alert("📡 Sinal de GPS indisponível. Verifique se o GPS do seu dispositivo está ligado.");
      } else if (erro.code === 3) {
        alert("⏱️ O GPS demorou muito para responder.");
      } else {
        alert("Erro desconhecido ao pegar localização.");
      }
    },
    // OPÇÕES (Melhora a precisão)
    {
      enableHighAccuracy: true, // Tenta usar GPS real
      timeout: 10000,           // Espera no máximo 10s
      maximumAge: 0             // Não usa cache velho
    }
  );
};

const processarRespostaClima = (d) => {
  const atual = d.list[0];
  const estadoDetectado = d.city.state_uf || d.city.state || estadoSelecionado.value || '';

  dadosClima.value = {
    ...atual,
    name: d.city.name,
    city_original: d.city.city_original || null,
    coord: d.city.coord,
    timezone: d.city.timezone,
    country: d.city.country,
    state: estadoDetectado,

    // NOVIDADE: Salvar nascer e pôr do sol para a matemática
    sys: {
      sunrise: d.city.sunrise,
      sunset: d.city.sunset
    },

    air_quality: d.air_quality || null,
    nearby: d.nearby || []
  };

  estadoSelecionado.value = '';

  previsaoHoraria.value = d.list.slice(0, 8);
  previsaoSemana.value = d.list.filter(i => i.dt_txt.includes("12:00:00"));
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
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.fade-slide-enter-active {
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

@keyframes float {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-10px);
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

.leaflet-container {
  z-index: 10 !important;
}
</style>