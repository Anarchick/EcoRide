import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
async function createChart(canvasId, config) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error(`Canvas with the ID "${canvasId}" not found`);
        return null;
    }
    return new Chart(ctx, config);
}
async function loadChart(canvas) {
    const chartType = canvas.dataset.chart;
    const chartDataRaw = canvas.dataset.chartData;
    if (!chartType || !chartDataRaw) {
        console.warn('Missing graph data on the canvas: ', canvas.id);
        return;
    }
    try {
        const chartData = JSON.parse(chartDataRaw);
        const config = {
            type: chartType,
            data: chartData,
            options: {
                responsive: true
            }
        };
        await createChart(canvas.id, config);
    }
    catch (error) {
        console.error('Error while parsing the graph data:', error, canvas.id);
    }
}
async function loadCharts() {
    const chartElements = document.querySelectorAll('canvas[data-chart]');
    for (const canvas of chartElements) {
        await loadChart(canvas);
    }
}
document.addEventListener('DOMContentLoaded', () => {
    loadCharts();
});
