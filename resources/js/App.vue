<template>
  <div class="min-h-screen bg-slate-50 p-6 flex flex-col items-center font-sans text-slate-900 pb-20">
    <header class="w-full max-w-4xl flex justify-between items-center mb-10">
      <div class="flex items-center gap-2">
        <h1 class="text-3xl font-black text-blue-600 tracking-tighter">
          SKYCAST <span class="text-slate-400 font-light italic">PRO</span>
        </h1>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold text-blue-500 italic">Software Engineer</p>
        <p class="text-xs text-slate-400 font-medium italic">Laravel + Vue.js + OpenWeather</p>
      </div>
    </header>

    <div class="w-full max-w-md relative mb-6">
      <div class="flex gap-3">
        <div class="relative flex-1">
          <input 
            v-model="cidadeInput" 
            @input="buscarSugestoes"
            @keyup.enter="executarBuscaFinal(cidadeInput)"
            type="text" 
            placeholder="Digite a cidade..." 
            class="w-full px-6 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all bg-white"
          />
          
          <ul v-if="sugestoes.length > 0" class="absolute z-50 w-full bg-white mt-2 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
            <li 
              v-for="(cidade, index) in sugestoes" :key="index"
              @mousedown.prevent="selecionarCidade(cidade)"
              class="px-6 py-4 hover:bg-blue-50 cursor-pointer flex justify-between items-center"
            >
              <div class="flex flex-col">
                <span class="font-bold text-slate-700">{{ cidade.name }}</span>
                <span class="text-[10px] text-slate-400 uppercase">{{ cidade.country }}</span>
              </div>
              <span class="text-[10px] font-black bg-blue-100 text-blue-600 px-2 py-1 rounded uppercase">
                {{ cidade.state || 'UF' }}
              </span>
            </li>
          </ul>
        </div>
        <button @click="executarBuscaFinal(cidadeInput)" class="bg-blue-600 text-white px-8 py-4 rounded-2xl shadow-lg font-bold">BUSCAR</button>
      </div>
    </div>

    <transition name="fade-slide">
      <div v-if="dadosClima" class="w-full max-w-md bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl relative overflow-hidden z-10">
        <div class="absolute -top-20 -right-20 w-64 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="relative z-10 text-center">
          <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1 italic">{{ dadosClima.weather[0].description }}</p>
          <h2 class="text-3xl font-extrabold mb-6">{{ nomeExibicao || dadosClima.name }} <span class="text-slate-500 italic">- {{ dadosClima.state }}</span></h2>
          <div class="flex items-center justify-center gap-4 mb-8">
            <span class="text-8xl font-black">{{ Math.round(dadosClima.main.temp) }}°</span>
            <span class="text-6xl">{{ obterIcone(dadosClima.main.temp) }}</span>
          </div>
        </div>
      </div>
    </transition>

    <MapWidget 
      v-if="dadosClima && dadosClima.coord" 
      :lat="dadosClima.coord.lat" 
      :lon="dadosClima.coord.lon" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import MapWidget from './components/MapWidget.vue'; // Importando o componente novo

const cidadeInput = ref('');
const sugestoes = ref([]);
const dadosClima = ref(null);
const nomeExibicao = ref(null);
const carregando = ref(false);
const historico = ref([]);

onMounted(() => {
  const salvo = localStorage.getItem('skycast_historico');
  if (salvo) historico.value = JSON.parse(salvo);
});

const buscarSugestoes = async () => {
  if (cidadeInput.value.length < 3) return;
  const resposta = await axios.get(`/api/cidades/busca/${encodeURIComponent(cidadeInput.value)}`);
  sugestoes.value = resposta.data;
};

const selecionarCidade = (cidadeObj) => {
  nomeExibicao.value = cidadeObj.name;
  const termo = `${cidadeObj.name} - ${cidadeObj.state}, ${cidadeObj.country}`;
  cidadeInput.value = termo;
  sugestoes.value = [];
  executarBuscaFinal(termo);
};

const executarBuscaFinal = async (termo) => {
  carregando.value = true;
  try {
    const resposta = await axios.get(`/api/clima/${encodeURIComponent(termo.replace(/\s*-\s*/, '-'))}`);
    dadosClima.value = resposta.data;
    cidadeInput.value = '';
  } finally {
    carregando.value = false;
  }
};

const obterIcone = (temp) => (temp > 25 ? '☀️' : '☁️');
</script>