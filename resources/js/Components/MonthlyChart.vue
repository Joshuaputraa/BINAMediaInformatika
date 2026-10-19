<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import Chart from 'chart.js/auto'
Chart.defaults.font.family = '"Inter Variable", Inter, sans-serif'

const props = defineProps({ data: Array })
const canvas = ref(null)
let chart

const labels = () =>
    props.data.map((d) => {
        const [y, m] = d.month.split('-')
        return new Date(y, m - 1).toLocaleDateString('id-ID', { month: 'short', year: '2-digit' })
    })

onMounted(() => {
    chart = new Chart(canvas.value, {
        type: 'bar',
        data: {
            labels: labels(),
            datasets: [{
                label: 'Siswa bermasalah',
                data: props.data.map((d) => d.total),
                backgroundColor: '#2563eb',
                borderRadius: 4,
                maxBarThickness: 28,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } },
            },
        },
    })
})

onBeforeUnmount(() => chart?.destroy())
</script>

<template>
    <div class="h-64"><canvas ref="canvas"></canvas></div>
</template>