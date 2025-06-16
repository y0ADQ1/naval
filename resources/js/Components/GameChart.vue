<template>
    <div class="bg-gray-700 p-4 rounded-lg">
        <canvas ref="chartCanvas"></canvas>

    </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
    props: {
        stats: Object,
    },
    data() {
        return {
            chart: null
        };
    },
    mounted() {
        this.renderChart();
    },
    watch: {
        stats: {
            handler() {
                // Destroy previous chart if it exists
                if (this.chart) {
                    this.chart.destroy();
                }
                this.$nextTick(() => {
                    this.renderChart();
                });
            },
            deep: true
        }
    },
    methods: {
        renderChart() {
            const ctx = this.$refs.chartCanvas.getContext('2d');

            // Define chart data
            const data = {
                labels: ['Jugadas', 'Ganadas', 'Perdidas'],
                datasets: [{
                    label: 'Estadísticas de partidas',
                    data: [this.stats.played, this.stats.won, this.stats.lost],
                    backgroundColor: ['#4B5563', '#10B981', '#EF4444'],
                    borderColor: ['#374151', '#059669', '#DC2626'],
                    borderWidth: 1,
                }],
            };

            // Define chart options
            const options = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#D1D5DB' },
                        grid: { color: '#4B5563' },
                    },
                    x: {
                        beginAtZero: true,
                        ticks: { color: '#D1D5DB' },
                        grid: { color: '#4B5563' },
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: '#D1D5DB' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        // Map index to filter type
                        const filterTypes = ['all', 'won', 'lost'];
                        this.$emit('chart-click', filterTypes[index]);
                    }
                }
            };

            // Create the chart
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: options
            });
        },
    },
};
</script>
