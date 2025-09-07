<template>
    <section class="dashboard">
        <header class="dashboard__header">
            <h1 class="dashboard__title">Dashboard</h1>
            <router-link class="button" to="/tickets">Go to Tickets</router-link>
        </header>

        <div v-if="loading" class="dashboard__loading">Loading dashboard…</div>
        <div v-else-if="error" class="dashboard__error">{{ error }}</div>
        <div v-else class="dashboard__content">
            <div class="cards">
                <div class="cards__group">
                    <h2 class="cards__title">By Status</h2>
                    <div class="cards__row">
                        <div class="card card--status card--status-open">
                            <div class="card__label">Open</div>
                            <div class="card__value">{{ stats.status.open }}</div>
                        </div>
                        <div class="card card--status card--status-pending">
                            <div class="card__label">Pending</div>
                            <div class="card__value">{{ stats.status.pending }}</div>
                        </div>
                        <div class="card card--status card--status-closed">
                            <div class="card__label">Closed</div>
                            <div class="card__value">{{ stats.status.closed }}</div>
                        </div>
                    </div>
                </div>

                <div class="cards__group">
                    <h2 class="cards__title">By Category</h2>
                    <div class="cards__row cards__row--wrap">
                        <div v-for="(count, cat) in stats.categories" :key="cat" class="card card--category" :class="'card--category-' + cat">
                            <div class="card__label">{{ cat }}</div>
                            <div class="card__value">{{ count }}</div>
                        </div>
                        <div v-if="Object.keys(stats.categories).length===0" class="cards__empty">No categories yet.</div>
                    </div>
                </div>
            </div>

            <div class="chart">
                <h2 class="chart__title">Tickets by Category</h2>
                <canvas ref="catCanvas" class="chart__canvas" width="640" height="320"></canvas>
            </div>
        </div>
    </section>
</template>

<script>
    import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Title, Tooltip } from "chart.js"
    Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Title)

    export default {
        name: "DashboardView",
        data() {
            return {
                loading: false,
                error: null,
                stats: {
                    total: 0,
                    status: { open: 0, pending: 0, closed: 0 },
                    categories: {},
                },
                chart: null,
            }
        },
        created() {
            this.fetchStats()
        },
        beforeUnmount() {
            if (this.chart) {
                try { this.chart.destroy() } catch (_) {}
                this.chart = null
            }
        },
        methods: {
            async fetchStats() {
                this.loading = true
                this.error = null
                try {
                    const res = await window.axios.get("/api/tickets/stats")
                    const data = res.data || {}
                    this.stats.total = data.total || 0
                    this.stats.status = data.status || { open: 0, pending: 0, closed: 0 }
                    this.stats.categories = data.categories || {}
                    this.$nextTick(() => this.drawCategoryChart())
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error(e)
                    this.error = "Failed to load dashboard stats"
                } finally {
                    this.loading = false
                }
            },
            drawCategoryChart() {
                const canvas = this.$refs.catCanvas
                if (!canvas) return

                const categories = Object.keys(this.stats.categories)
                const values = categories.map(k => this.stats.categories[k])

                if (this.chart) {
                    try { this.chart.destroy() } catch (_) {}
                    this.chart = null
                }

                if (categories.length === 0) {
                    // Render a tiny placeholder using Chart.js with empty data to keep DOM consistent
                    const ctx = canvas.getContext("2d")
                    ctx.clearRect(0, 0, canvas.width, canvas.height)
                    ctx.fillStyle = "#666"
                    ctx.font = "14px system-ui, -apple-system, Arial"
                    ctx.fillText("No category data yet", 16, 24)
                    return
                }

                const palette = ["#2f6fed", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6", "#06b6d4", "#84cc16", "#f472b6"]
                const bgColors = categories.map((_, idx) => palette[idx % palette.length])

                this.chart = new Chart(canvas, {
                    type: "bar",
                    data: {
                        labels: categories,
                        datasets: [
                            {
                                label: "Tickets",
                                data: values,
                                backgroundColor: bgColors,
                                borderColor: bgColors,
                            }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            title: { display: false },
                            tooltip: { enabled: true },
                        },
                        scales: {
                            x: { ticks: { color: "#333" }, grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0, color: "#333" } },
                        },
                    },
                })
            },
        },
    }
</script>

<style>
    .dashboard {
        max-width: 1200px;
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

    .cards__group {
    }

    .cards__title {
        margin: 0 0 8px;
        font-size: 16px;
        color: #333;
    }

    .cards__row {
        display: flex;
        gap: 12px;
    }

    .cards__row--wrap {
        flex-wrap: wrap;
    }

    .cards__empty {
        color: #777;
        padding: 8px 0;
    }

    .card {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 12px;
        min-width: 120px;
        background: #fafafa;
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

    .card--category-billing {
        border-color: #fed7aa;
        background: #fff7ed;
    }

    .card--category-technical {
        border-color: #a5f3fc;
        background: #ecfeff;
    }

    .card--category-account {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .card--category-sales {
        border-color: #fbcfe8;
        background: #fdf2f8;
    }

    .card--category-general {
        border-color: #c7d2fe;
        background: #eef2ff;
    }

    .chart {
    }

    .chart__title {
        margin: 0 0 8px;
    }

    .chart__canvas {
        width: 100%;
        max-width: 800px;
        height: 320px;
        border: 1px solid #eee;
        border-radius: 4px;
        background: #fff;
    }
</style>
