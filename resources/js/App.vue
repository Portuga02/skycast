<template>
  <div class="min-h-screen bg-slate-50 p-6 flex flex-col items-center font-sans text-slate-900">
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

    <div class="w-full max-w-md relative mb-10">
      <div class="flex gap-3">
        <div class="relative flex-1">
          <input 
            v-model="cidadeInput" 
            @input="buscarSugestoes"
            @keyup.enter="executarBuscaFinal(cidadeInput)"
            type="text" 
            placeholder="Digite a cidade (Ex: Prata)..." 
            class="w-full px-6 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all text-slate-700 bg-white"
          />
          
          <ul v-if="sugestoes.length > 0" class="absolute z-50 w-full bg-white mt-2 rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
            <li 
              v-for="(cidade, index) in sugestoes" 
              :key="index"
              @mousedown.prevent="selecionarCidade(cidade)"
              class="px-6 py-4 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-none flex justify-between items-center transition-colors"
            >
              <div class="flex flex-col">
                <span class="font-bold text-slate-700">{{ cidade.name }}</span>
                <span class="text-[10px] text-slate-400 uppercase tracking-wide">{{ cidade.country }}</span>
              </div>
              <span class="text-[10px] font-black bg-blue-100 text-blue-600 px-2 py-1 rounded shadow-sm uppercase">
                {{ cidade.state || 'UF' }}
              </span>
            </li>
          </ul>
        </div>
        
        <button 
          @click="executarBuscaFinal(cidadeInput)"
          :disabled="carregando"
          class="bg-blue-600 text-white px-8 py-4 rounded-2xl hover:bg-blue-700 transition-all shadow-lg font-bold disabled:opacity-50"
        >
          {{ carregando ? '...' : 'BUSCAR' }}
        </button>
      </div>
    </div>

    <transition name="fade-slide">
      <div v-if="dadosClima" class="w-full max-w-md bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl border border-slate-800 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>
        <div class="relative z-10 text-center">
          <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1 italic">
            {{ dadosClima.weather[0].description }}
          </p>
          
          <h2 class="text-3xl font-extrabold tracking-tight mb-6">
            {{ nomeExibicao || dadosClima.name }} 
            <span v-if="dadosClima.state" class="text-slate-500 font-medium italic"> - {{ dadosClima.state }}</span>
          </h2>
          
          <div class="flex items-center justify-center gap-4 mb-8">
            <span class="text-8xl font-black tracking-tighter">{{ Math.round(dadosClima.main.temp) }}°</span>
            <span class="text-6xl drop-shadow-xl">{{ obterIcone(dadosClima.main.temp) }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4 pt-8 border-t border-slate-800">
            <div class="flex flex-col bg-slate-800/50 p-4 rounded-2xl">
              <span class="text-[10px] uppercase font-bold text-slate-500 mb-1">Umidade</span>
              <span class="text-white font-bold text-lg">{{ dadosClima.main.humidity }}%</span>
            </div>
            <div class="flex flex-col bg-slate-800/50 p-4 rounded-2xl">
              <span class="text-[10px] uppercase font-bold text-slate-500 mb-1">Vento</span>
              <span class="text-white font-bold text-lg">{{ dadosClima.wind.speed }} km/h</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="erroMensagem" class="mt-6 max-w-md w-full text-red-600 font-bold bg-red-50 px-6 py-4 rounded-2xl border border-red-100 shadow-sm text-center">
        <p>⚠️ {{ erroMensagem }}</p>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const cidadeInput = ref('');
const sugestoes = ref([]);
const dadosClima = ref(null);
const nomeExibicao = ref(null); // Nova variável para guardar o nome correto
const carregando = ref(false);
const erroMensagem = ref(null);
const historico = ref([]);

onMounted(() => {
  const salvo = localStorage.getItem('skycast_historico');
  if (salvo) historico.value = JSON.parse(salvo);
});

const buscarSugestoes = async () => {
  if (cidadeInput.value.length < 3) {
    sugestoes.value = [];
    return;
  }
  try {
    const query = encodeURIComponent(cidadeInput.value.trim());
    const resposta = await axios.get(`/api/cidades/busca/${query}`);
    
    // Filtro de duplicatas
    const unique = [];
    const map = new Map();
    for (const item of resposta.data) {
        if(!map.has(item.full_name)){
            map.set(item.full_name, true);
            unique.push(item);
        }
    }
    sugestoes.value = unique;
  } catch (e) {
    console.error("Erro nas sugestões");
  }
};

// 1. SELEÇÃO DA CIDADE
const selecionarCidade = (cidadeObj) => {
  // Guardamos o nome bonito (Ex: "Prata") na variável de exibição
  nomeExibicao.value = cidadeObj.name; 
  
  // Montamos a string técnica para a API achar o lugar certo (com País)
  const termoTecnico = `${cidadeObj.name} - ${cidadeObj.state}, ${cidadeObj.country}`;
  
  cidadeInput.value = termoTecnico;
  sugestoes.value = [];
  
  executarBuscaFinal(termoTecnico);
};

// 2. BUSCA FINAL
const executarBuscaFinal = async (termoDeBusca) => {
  const queryFinal = termoDeBusca || cidadeInput.value;
  if (!queryFinal || queryFinal.trim() === '') return;
  
  // Se for uma busca digitada manualmente (Enter), limpamos o nome personalizado
  if (!termoDeBusca) nomeExibicao.value = null; 

  carregando.value = true;
  erroMensagem.value = null;
  sugestoes.value = [];

  try {
    const termoLimpo = queryFinal.trim().replace(/\s*-\s*/, '-');
    const queryCodificada = encodeURIComponent(termoLimpo);
    
    const resposta = await axios.get(`/api/clima/${queryCodificada}`);
    
    if (resposta.data) {
      dadosClima.value = resposta.data;
      
      // Se não definimos um nome via clique, usamos o da API
      if (!nomeExibicao.value) {
          nomeExibicao.value = dadosClima.value.name;
      }
      
      atualizarHistorico(queryFinal.trim());
      cidadeInput.value = ''; 
    }
  } catch (erro) {
    dadosClima.value = null;
    erroMensagem.value = "Localização não encontrada";
  } finally {
    carregando.value = false;
  }
};

const atualizarHistorico = (termo) => {
  historico.value = historico.value.filter(item => item.toLowerCase() !== termo.toLowerCase());
  historico.value.unshift(termo);
  if (historico.value.length > 5) historico.value.pop();
  localStorage.setItem('skycast_historico', JSON.stringify(historico.value));
};

const obterIcone = (temp) => {
  if (temp > 30) return '🔥';
  if (temp > 22) return '☀️';
  if (temp > 15) return '☁️';
  return '❄️';
};
</script>

<style scoped>
.fade-slide-enter-active { transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-slide-enter-from { opacity: 0; transform: translateY(30px) scale(0.98); }
</style>