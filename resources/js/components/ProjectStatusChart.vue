<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { Chart, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

// Register required Chart.js components
Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

interface StatusItem {
  status_name: string;
  count: number;
}

const props = defineProps<{
  statusData: StatusItem[];
}>();

const chartRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const renderChart = () => {
  if (!chartRef.value) return;
  const ctx = chartRef.value.getContext('2d');
  if (!ctx) return;

  const data = {
    labels: props.statusData.map(d => d.status_name),
    datasets: [
      {
        label: 'عدد المشاريع',
        data: props.statusData.map(d => d.count),
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
        borderRadius: 4,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 } },
    },
  };

  if (chartInstance) {
    chartInstance.destroy();
  }
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data,
    options,
  });
};

onMounted(() => {
  renderChart();
});

watch(() => props.statusData, () => {
  renderChart();
});
</script>

<template>
  <div class="w-full h-80">
    <canvas ref="chartRef"></canvas>
  </div>
</template>

<style scoped>
/* Optional styling to give the chart container some spacing */
</style>
