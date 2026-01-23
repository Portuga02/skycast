<template>
  <div class="w-full h-[250px]">
    <component 
      :is="tipo === 'line' ? Line : Bar" 
      :data="chartData" 
      :options="chartOptions" 
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Line, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Filler,
  Legend
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Filler, Legend);

const props = defineProps(['horas', 'isDark', 'timezone', 'tipo']);

const chartData = computed(() => {
  // Guardião contra o erro de "Invalid Date"
  if (!props.horas || props.horas.length === 0 || props.timezone === undefined) {
    return { labels: [], datasets: [] };
  }

  const labels = props.horas.map(h => {
    try {
      const dtValue = typeof h.dt === 'string' ? parseInt(h.dt) : h.dt;
      const agora = new Date(dtValue * 1000);
      const utc = agora.getTime() + (agora.getTimezoneOffset() * 60000);
      const dataLocal = new Date(utc + (1000 * props.timezone));
      
      return dataLocal.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
      return '--:--';
    }
  });

  const temps = props.horas.map(h => Math.round(h.main.temp));

  return {
    labels,
    datasets: [{
      label: 'Temperatura (°C)',
      data: temps,
      fill: true,
      borderColor: props.isDark ? '#60a5fa' : '#2563eb',
      // No gráfico de barras, usamos uma cor mais sólida
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return null;
        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        gradient.addColorStop(0, props.isDark ? 'rgba(96, 165, 250, 0.4)' : 'rgba(37, 99, 235, 0.2)');
        gradient.addColorStop(1, 'transparent');
        return props.tipo === 'line' ? gradient : (props.isDark ? '#3b82f6' : '#2563eb');
      },
      tension: 0.45,
      pointRadius: props.tipo === 'line' ? 5 : 0,
      borderWidth: props.tipo === 'line' ? 4 : 0,
      borderRadius: 8, // Arredondado para o gráfico de barras
    }]
  };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: props.isDark ? '#1e293b' : '#ffffff',
      titleColor: props.isDark ? '#ffffff' : '#1e293b',
      bodyColor: props.isDark ? '#ffffff' : '#1e293b',
      callbacks: { label: (ctx) => ` ${ctx.raw}°C` }
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { weight: 'bold' } } },
    y: { 
      grid: { color: props.isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }, 
      ticks: { color: '#94a3b8' } 
    }
  }
}));
</script>