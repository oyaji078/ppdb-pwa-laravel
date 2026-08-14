import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController,
    Filler, Legend, LineController, LineElement, LinearScale, PointElement, Tooltip,
);

Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;

/**
 * Every chart reads its data from a JSON script tag rendered by Blade, so the
 * numbers always come from the database and never from a literal in JS.
 */
const readData = (canvas) => {
    const source = document.getElementById(canvas.dataset.source);

    if (!source) {
        return null;
    }

    try {
        return JSON.parse(source.textContent);
    } catch {
        return null;
    }
};

const emptyState = (canvas) => {
    const holder = canvas.closest('[data-chart-holder]');

    if (holder) {
        canvas.classList.add('hidden');
        holder.querySelector('[data-chart-empty]')?.classList.remove('hidden');
    }
};

const buildLine = (canvas, data) => new Chart(canvas, {
    type: 'line',
    data: {
        labels: data.labels,
        datasets: [{
            label: 'Pendaftar',
            data: data.values,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#2563eb',
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                grid: { color: '#e2e8f0' },
            },
            x: { grid: { display: false } },
        },
    },
});

const buildDoughnut = (canvas, data) => new Chart(canvas, {
    type: 'doughnut',
    data: {
        labels: data.labels,
        datasets: [{
            data: data.values,
            backgroundColor: data.colors ?? ['#3b82f6', '#6366f1', '#f59e0b', '#10b981'],
            borderWidth: 0,
            hoverOffset: 6,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom' },
        },
    },
});

const buildBar = (canvas, data) => new Chart(canvas, {
    type: 'bar',
    data: {
        labels: data.labels,
        datasets: [{
            label: 'Pendaftar',
            data: data.values,
            backgroundColor: '#3b82f6',
            borderRadius: 6,
            maxBarThickness: 42,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e2e8f0' } },
            x: { grid: { display: false } },
        },
    },
});

const builders = { line: buildLine, doughnut: buildDoughnut, bar: buildBar };

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const data = readData(canvas);

        if (!data || !data.values || data.values.every((value) => value === 0)) {
            emptyState(canvas);

            return;
        }

        builders[canvas.dataset.chart]?.(canvas, data);
    });
});
