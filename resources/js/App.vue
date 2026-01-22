<template>
  <div class="min-h-screen bg-slate-50 flex flex-col items-center font-sans text-slate-900 pb-20 overflow-x-hidden">

    <div class="w-full max-w-6xl px-6 pt-10 flex flex-col items-center">
      <header class="w-full flex justify-between items-center mb-8">
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-black text-blue-600 tracking-tighter uppercase">
            SKYCAST <span class="text-slate-400 font-light italic">PRO</span>
          </h1>
        </div>
        <div class="text-right hidden sm:block">
          <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold text-blue-500 italic">Software
            Engineer</p>
          <p class="text-xs text-slate-400 font-medium italic">Laravel + Vue.js + OpenWeather</p>
        </div>
      </header>

      <div class="w-full max-w-md relative mb-10 z-50">
        <div class="flex gap-3">
          <div class="relative flex-1">
            <div class="relative flex-1">
              <input v-model="cidadeInput" @input="buscarSugestoes" @keyup.enter="executarBuscaFinal(cidadeInput)"
                type="text" placeholder="Digite a cidade ou use sua localização..."
                class="w-100 pl-6 pr-16 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all bg-white truncate" />
              <button @click="usarLocalizacao"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors p-2"
                title="Usar minha localização atual">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                  stroke="currentColor" class="w-7 h-7">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
              </button>

              <ul v-if="sugestoes.length > 0"
                class="absolute z-50 w-full bg-white mt-2 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
              </ul>
            </div>

            <ul v-if="sugestoes.length > 0"
              class="absolute z-50 w-full bg-white mt-2 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
              <li v-for="(cidade, index) in sugestoes" :key="index" @mousedown.prevent="selecionarCidade(cidade)"
                class="px-6 py-4 hover:bg-blue-50 cursor-pointer flex justify-between items-center border-b border-slate-50 last:border-none">
                <div class="flex flex-col">
                  <span class="font-bold text-slate-700">{{ cidade.name }}</span>
                  <span class="text-[10px] text-slate-400 uppercase tracking-wider">{{ cidade.country }}</span>
                </div>
                <span class="text-[10px] font-black bg-blue-100 text-blue-600 px-2 py-1 rounded uppercase">
                  {{ cidade.state || 'UF' }}
                </span>
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
        <div
          class="w-full bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl relative overflow-hidden min-h-[400px] flex flex-col justify-center">
          <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>
          <div class="relative z-10 text-center">
            <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1 italic">
              {{ dadosClima.weather[0].description }}
            </p>
            <h2 class="text-4xl font-extrabold mb-6 tracking-tight">
              {{ nomeExibicao || dadosClima.name }}
              <span class="text-slate-500 italic font-medium block text-xl mt-1"> {{ dadosClima.state ? dadosClima.state
                + ' - BR' : 'BR' }}</span>
            </h2>
            <div class="flex items-center justify-center gap-6 mb-4">
              <span class="text-8xl font-black tracking-tighter">{{ Math.round(dadosClima.main.temp) }}°</span>
              <span class="text-6xl drop-shadow-2xl">{{ obterIcone(dadosClima.main.temp) }}</span>
            </div>
            <div
              class="flex justify-center gap-8 mt-6 pt-6 border-t border-slate-800 text-slate-400 text-xs uppercase font-bold tracking-widest">
              <span class="flex items-center gap-1">💧 {{ dadosClima.main.humidity }}%</span>
              <span class="flex items-center gap-1">🌬️ {{ Math.round(dadosClima.wind.speed) }} km/h</span>
            </div>
          </div>
        </div>
      </transition>

      <div class="w-full h-[400px] rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white relative z-0">
        <MapWidget v-if="dadosClima && dadosClima.coord" :lat="dadosClima.coord.lat" :lon="dadosClima.coord.lon" />
      </div>

    </div>

    <div v-if="previsaoSemana.length > 0" class="w-full max-w-6xl px-6 z-20">
      <div class="flex items-center gap-4 mb-6">
        <div class="h-px bg-slate-200 flex-1"></div>
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Previsão 5 Dias</h3>
        <div class="h-px bg-slate-200 flex-1"></div>
      </div>

      <div class="flex gap-4 overflow-x-auto pb-6 snap-x no-scrollbar justify-between">
        <div v-for="dia in previsaoSemana" :key="dia.dt"
          class="flex-1 min-w-[140px] snap-center bg-white p-6 rounded-[2rem] shadow-lg border border-slate-100 flex flex-col items-center text-center hover:scale-105 transition-transform duration-300">
          <span class="text-[10px] font-bold text-blue-500 uppercase mb-3">
            {{ new Date(dia.dt * 1000).toLocaleDateString('pt-BR', { weekday: 'short' }) }}
          </span>
          <span class="text-3xl mb-2">{{ obterIcone(dia.main.temp) }}</span>
          <span class="text-2xl font-black text-slate-800">{{ Math.round(dia.main.temp) }}°</span>
          <span class="text-[10px] text-slate-400 font-bold uppercase mt-2 leading-tight">
            {{ dia.weather[0].description }}
          </span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import MapWidget from './components/MapWidget.vue';

const cidadeInput = ref('');
const sugestoes = ref([]);
const dadosClima = ref(null);
const nomeExibicao = ref(null);
const carregando = ref(false);
const previsaoSemana = ref([]);

const buscarSugestoes = async () => {
  if (cidadeInput.value.length < 3) {
    sugestoes.value = [];
    return;
  }
  try {
    const resposta = await axios.get(`/api/cidades/busca/${encodeURIComponent(cidadeInput.value)}`);
    sugestoes.value = resposta.data;
  } catch (e) {
    console.error("Erro nas sugestões");
  }
};

const selecionarCidade = (cidadeObj) => {
  nomeExibicao.value = cidadeObj.name;
  const termo = `${cidadeObj.name} - ${cidadeObj.state || ''}, ${cidadeObj.country}`;
  cidadeInput.value = termo;
  sugestoes.value = [];
  executarBuscaFinal(termo);
};

const executarBuscaFinal = async (termo) => {
  if (!termo) return;
  carregando.value = true;
  sugestoes.value = [];
  try {
    const termoLimpo = termo.replace(/\s*-\s*/, '-');
    const resposta = await axios.get(`/api/clima/${encodeURIComponent(termoLimpo)}`);
    if (resposta.data && resposta.data.list) {
      const climaAtual = resposta.data.list[0];
      dadosClima.value = {
        ...climaAtual,
        name: resposta.data.city.name,
        coord: resposta.data.city.coord,
        state: resposta.data.city.state_uf || ''
      };
      previsaoSemana.value = resposta.data.list.filter(item => item.dt_txt.includes("12:00:00"));
      cidadeInput.value = '';
    }
  } catch (erro) {
    console.error("Erro na busca detalhada:", erro);
  } finally {
    carregando.value = false;
  }
};

const obterIcone = (temp) => {
  if (temp > 30) return '🔥';
  if (temp > 22) return '☀️';
  if (temp > 15) return '☁️';
  return '❄️';
};
const usarLocalizacao = () => {
  if (!navigator.geolocation) {
    alert("Seu navegador não suporta geolocalização.");
    return;
  }

  carregando.value = true;

  // Opções para tentar forçar a melhor precisão possível
  const opcoes = {
    enableHighAccuracy: true, // Tenta usar GPS se disponível ou WiFi mais preciso
    timeout: 10000,           // Espera até 10s para conseguir um sinal bom
    maximumAge: 0             // Não usa cache de posição antiga
  };

  navigator.geolocation.getCurrentPosition(
    async (posicao) => {
      try {
        const { latitude, longitude } = posicao.coords;
        
        const resposta = await axios.get(`/api/clima/coordenadas`, {
          params: { lat: latitude, lon: longitude }
        });

        if (resposta.data && resposta.data.list) {
          processarRespostaClima(resposta.data);
          cidadeInput.value = ''; 
        }
      } catch (erro) {
        console.error("Erro na geolocalização:", erro);
        alert("Erro ao buscar dados da sua localização.");
      } finally {
        carregando.value = false;
      }
    },
    (erro) => {
      console.error(erro);
      carregando.value = false;
      alert("Não foi possível obter sua localização precisa.");
    },
    opcoes // <--- O SEGREDO ESTÁ AQUI
  );
};

// Dica de Refatoração: Criei essa função auxiliar para não repetir código
// Mova a lógica de preencher o `dadosClima` e `previsaoSemana` para cá
const processarRespostaClima = (dados) => {
  const climaAtual = dados.list[0];

  dadosClima.value = {
    ...climaAtual,
    name: dados.city.name,
    coord: dados.city.coord,
    state: dados.city.state_uf || ''
  };

  previsaoSemana.value = dados.list.filter(item => item.dt_txt.includes("12:00:00"));
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
</style>