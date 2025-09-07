<template>
    <section class="dashboard">
        <header class="dashboard__header">
            <h1 class="text--h1">Dashboard</h1>
            <router-link class="link--button text--small" to="/tickets">Go to Tickets</router-link>
        </header>

        <div v-if="loading" class="dashboard__loading">Loading dashboard…</div>
        <div v-else-if="error" class="dashboard__error">{{ error }}</div>
        <div v-else class="dashboard__content">
            <div class="cards">
                <h2 class="text--h2">Tickets by Status</h2>
                <div class="cards__row">
                    <div class="card card--status card--status-open">
                        <div class="card__label">Open</div>
                        <div class="card__value">{{ stats.open }}</div>
                    </div>
                    <div class="card card--status card--status-pending">
                        <div class="card__label">Pending</div>
                        <div class="card__value">{{ stats.pending }}</div>
                    </div>
                    <div class="card card--status card--status-closed">
                        <div class="card__label">Closed</div>
                        <div class="card__value">{{ stats.closed }}</div>
                    </div>
                </div>
            </div>
            <div class="chart">
                <h2 class="text--h2">Tickets by Category</h2>
                <Bar
                    id="issues-by-category"
                    :options="chartOptions"
                    :data="chartData"
                    :style="{ width: '100%', height: '400px', backgroundColor: 'var(--color-bg-panel)', borderRadius: '8px' }"/>
            </div>
        </div>
    </section>
</template>

<script>
    import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Title, Tooltip } from "chart.js"
    import { Bar } from "vue-chartjs"
    import { useHttp } from "../composables/useHttp.js"

    ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

    export default {
        name: "DashboardView",
        components: {
            Bar,
        },
        data() {
            return {
                loading: false,
                error: null,
                chartData: {
                    labels: [],
                    datasets: [],
                },
                chartOptions: {
                    responsive: true,
                },
            }
        },
        created() {
            this.fetchStats()
        },
        methods: {
            async fetchStats() {
                this.loading = true
                this.error = null

                await useHttp().get("/api/tickets/stats", {}, {
                    onSuccess: (res) => {
                        this.stats = res.status || { open: 0, pending: 0, closed: 0 }
                        this.chartData = res.chartData
                    },
                    onError: (e) => {
                        this.error = e.message || "Failed to load dashboard"
                    },
                    onFinally: () => {
                        this.loading = false
                    },
                })
            },
        },
    }
</script>

<style>
    .dashboard {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px;
    }

    .dashboard__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dashboard__title {
        margin: 0;
    }

    .dashboard__loading, .dashboard__error {
        padding: 12px;
        color: #555;
    }

    .cards {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 16px;
    }

    .cards__row {
        display: flex;
        gap: 12px;
        justify-content: space-between;
    }

    .card {
        width: 100%;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 12px;
        min-width: 120px;
        background: var(--color-bg-panel);
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .card__label {
        font-size: 12px;
        color: #555;
        margin-bottom: 4px;
    }

    .card__value {
        font-size: 24px;
        font-weight: 700;
    }

    .card--status-open {
        border-color: #c7d2fe;
        background: #eef2ff;
    }

    .card--status-pending {
        border-color: #fde68a;
        background: #fef3c7;
    }

    .card--status-closed {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .dashboard__content {
        margin: 0 auto;
        gap: 2rem;
        display: flex;
        flex-direction: column;
    }
</style>
