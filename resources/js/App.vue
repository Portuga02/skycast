<template>
  <div class="min-h-screen bg-slate-50 p-6 flex flex-col items-center font-sans">
    <header class="w-full max-w-4xl flex justify-between items-center mb-10">
      <h1 class="text-2xl font-black text-blue-600 tracking-tighter">
         SKYCAST <span class="text-slate-400 font-light">PRO</span>
      </h1>
      <div class="text-right hidden sm:block">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Software Engineering Project</p>
        <p class="text-xs text-blue-500 font-medium">PHP 8.4 + Laravel 12 + Vue 3</p>
      </div>
    </header>

    <div class="w-full max-w-md flex gap-3 mb-10">
      <input 
        v-model="cidade" 
        @keyup.enter="getWeather"
        type="text" 
        placeholder="Buscar cidade (ex: Recife)..." 
        class="flex-1 px-6 py-4 rounded-2xl border-none shadow-lg focus:ring-2 focus:ring-blue-500 transition-all text-slate-700"
      />
      <button 
        @click="getWeather"
        :disabled="loading"
        class="bg-blue-600 text-white px-8 py-4 rounded-2xl hover:bg-blue-700 transition-all shadow-lg font-bold disabled:opacity-50"
      >
        {{ loading ? '...' : 'BUSCAR' }}
      </button>
    </div>

    <transition name="fade">
      <div v-if="weather" class="w-full max-w-md bg-slate-900 text-white p-10 rounded-[2.5rem] shadow-2xl border border-slate-800 relative overflow-hidden">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1">{{ weather.weather[0].description }}</p>
              <h2 class="text-3xl font-extrabold tracking-tight">{{ weather.name }}</h2>
            </div>
            <span class="text-5xl">☀️</span>
          </div>
          
          <div class="my-8">
            <h3 class="text-8xl font-black tracking-tighter">{{ Math.round(weather.main.temp) }}°</h3>
          </div>

          <div class="grid grid-cols-2 gap-4 pt-6 border-t border-slate-800 text-sm text-slate-400">
            <div class="flex flex-col">
              <span class="text-[10px] uppercase font-bold text-slate-500">Umidade</span>
              <span class="text-white font-medium">{{ weather.main.humidity }}%</span>
            </div>
            <div class="flex flex-col">
              <span class="text-[10px] uppercase font-bold text-slate-500">Vento</span>
              <span class="text-white font-medium">{{ weather.wind.speed }} km/h</span>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <p v-if="error" class="mt-6 text-red-500 font-bold bg-red-50 px-6 py-3 rounded-2xl border border-red-100">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const cidade = ref('');
const weather = ref(null);
const loading = ref(false);
const error = ref(null);

const getWeather = async () => {
  if (!cidade.value) return;
  loading.value = true;
  error.value = null;

  try {
    // Note: Usamos a rota que você testou e deu certo no navegador
    const response = await axios.get(`/api/clima/${cidade.value}`);
    weather.value = response.data;
  } catch (err) {
    error.value = "Cidade não encontrada ou erro no servidor.";
    weather.value = null;
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(20px); }
</style>