import {
    Chart,
    ChartConfiguration,
    ChartType,
    registerables,
    ChartData
} from 'chart.js';

Chart.register(...registerables);

/*
    Chart are automatically render if a canvas with the data-chart attribute is found in the DOM.
    
    USAGES EXAMPLE:

    <canvas id="dailyChart" aria-label="Graphique des voyages" role="img" 
            data-chart="line" 
            data-chart-data='{
                "labels": {{ chartTravels|map(label => label.period)|json_encode }},
                "datasets": [{
                    "label": "Voyages à cette date",
                    "data": {{ chartTravels|map(data => data.count)|json_encode }},
                    "borderColor": "#198754",
                    "backgroundColor": "#85c26666",
                    "fill": true
                }]
            }'>
    </canvas>
*/

async function createChart(canvasId: string, config: ChartConfiguration): Promise<Chart | null> {
    const ctx: HTMLCanvasElement | null = document.getElementById(canvasId) as HTMLCanvasElement | null;

    if (!ctx) {
        console.error(`Canvas with the ID "${canvasId}" not found`);
        return null;
    }

    return new Chart(ctx, config);
}

// Load single chart from a canvas element
async function loadChart(canvas: HTMLCanvasElement): Promise<Chart | null | void> {
    const chartType: ChartType | undefined = canvas.dataset.chart as ChartType | undefined;
    const chartDataRaw: string | undefined = canvas.dataset.chartData;

    if (!chartType || !chartDataRaw) {
        console.warn('Missing graph data on the canvas: ', canvas.id);
        return;
    }

    try {
        const chartData: ChartData = JSON.parse(chartDataRaw);
        const config: ChartConfiguration = {
            type: chartType,
            data: chartData,
            options: { // TODO add option overwrite in html
                responsive: true
            }
        };
        await createChart(canvas.id, config);
    } catch (error) {
        console.error('Error while parsing the graph data:', error, canvas.id);
    }
}

// Auto-initialization if we find canvas with the data-chart attribute
async function loadCharts(): Promise<void> {
    const chartElements: NodeListOf<HTMLCanvasElement> = document.querySelectorAll('canvas[data-chart]') as NodeListOf<HTMLCanvasElement>;
    
    for (const canvas of chartElements) {
        await loadChart(canvas);
    }
}

document.addEventListener('DOMContentLoaded', (): void => {
    loadCharts();
});

// TODO HTMX support if needed in the future
