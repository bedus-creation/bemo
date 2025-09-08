<template>
    <section class="tickets">
        <header class="tickets__header">
            <h1 class="text--h1">Tickets</h1>
            <div class="tickets__actions">
                <button class="button button--primary text--small" @click="showNewModal = true">New Ticket</button>
                <a :href="`/api/tickets/export?${queryString}`" class="link--button text--small">CSV Export</a>
            </div>
        </header>

        <div class="tickets__filters">
            <input
                v-model="search"
                class="input input--search"
                type="text"
                placeholder="Search subject or body..."
                @input="applyFilters"
            />
            <select v-model="selectedCategory" class="select" @change="applyFilters">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="selectedStatus" class="select" @change="applyFilters">
                <option value="">All status</option>
                <option value="open">open</option>
                <option value="pending">pending</option>
                <option value="closed">closed</option>
            </select>
        </div>

        <div v-if="loading" class="tickets__loading">Loading tickets…</div>
        <div v-else>
            <div v-if="tickets.length === 0" class="tickets__empty">No tickets found.</div>
            <div class="ticket-table__wrapper" v-if="tickets.length">
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <th class="ticket-table__th">Subject</th>
                            <th class="ticket-table__th">Category</th>
                            <th class="ticket-table__th">Confidence</th>
                            <th class="ticket-table__th">Note</th>
                            <th class="ticket-table__th ticket-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in tickets" :key="t.id" class="ticket-table__row">
                            <td class="ticket-table__td">
                                <router-link class="ticket-table__subject" :to="{ name: 'ticket-detail', params: { id: t.id } }" :title="t.body">
                                    {{ t.subject }}
                                </router-link>
                                <div class="ticket-table__meta">{{ t.created_at.diff }}</div>
                            </td>
                            <td class="ticket-table__td">
                            <span class="badge">
                                <span v-if="t.classification"
                                      class="badge__dot"
                                      aria-hidden="true"
                                      :title="t.classification.explanation || 'No explanation available'">
                                      i
                                </span>
                              {{ t.category?.name || "—" }}
                            </span>
                            </td>
                            <td class="ticket-table__td">
                             <span v-if="t.classification" :title="t.classification.explanation || 'No explanation available'">
                               {{ t.classification?.confidence || 0 }}
                             </span>
                                <span v-else>—</span>
                            </td>
                            <td class="ticket-table__td">
                                <span v-if="t.note" class="note-badge" title="Internal note present">Note</span>
                                <span v-else>—</span>
                            </td>
                            <td class="ticket-table__td ticket-table__actions">
                                <button
                                    v-if="!t?.classification"
                                    class="button"
                                    :disabled="classifying[t.id]"
                                    @click="classifyTicket(t)"
                                >
                                    <span v-if="classifying[t.id]" class="spinner" aria-hidden="true"></span>
                                    {{ classifying[t.id] ? "Classifying…" : "Classify" }}
                                </button>
                                <button class="button" @click="editNote(t)">Edit Note</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" v-if="totalPages > 1">
                <button class="button" :disabled="page===1" @click="goToPage(page-1)">Prev</button>
                <span class="pagination__info">Page {{ page }} of {{ totalPages }}</span>
                <button class="button" :disabled="page===totalPages" @click="goToPage(page+1)">Next</button>
            </div>
        </div>

        <TicketNewModal
            :show="showNewModal"
            @close="showNewModal = false"
            @success="fetchTickets(); showNewModal = false;"/>

        <!-- Edit Note Modal -->
        <TicketNoteEdit
            :show="showNoteModal"
            :ticket="editingTicket"
            @close="closeNoteModal"
            @success="fetchTickets(); closeNoteModal();"
        />
    </section>
</template>

<script>
    import { useHttp } from "../composables/useHttp.js"
    import TicketNewModal from "./TicketNewModal.vue"
    import TicketNoteEdit from "./TicketNoteEdit.vue"
    export default {
        name: "TicketsView",
        components: {
            TicketNewModal,
            TicketNoteEdit,
        },
        data() {
            return {
                categories: [],
                tickets: [],
                loading: false,
                error: null,

                // filters
                search: "",
                selectedCategory: "",
                selectedStatus: "",

                // pagination
                page: 1,
                perPage: 10,
                totalPages: null,

                // classify state per ticket id
                classifying: {},
                pollers: {},

                // modal states
                showNewModal: false,
                showNoteModal: false,
                editingTicket: null,
            }
        },
        watch: {
            search: "fetchTickets",
            selectedCategory: "fetchTickets",
            selectedStatus: "fetchTickets",
            page: "fetchTickets",
        },
        computed: {
            queryString: function() {
                const params = new URLSearchParams({
                    per_page: this.perPage,
                    query: this.search.trim().toLowerCase(),
                    category: this.selectedCategory,
                    status: this.selectedStatus,
                    page: this.page,
                })

                return params.toString()
            },
        },
        created() {
            this.fetchCategories()

            // Improvement: package such as qs can be used to parse query params
            // @see https://www.npmjs.com/package/qs
            const params = new URLSearchParams(window.location.search)
            if (params.has("query")) this.search = params.get("query")
            if (params.has("category")) this.selectedCategory = params.get("category")
            if (params.has("status")) this.selectedStatus = params.get("status")
            if (params.has("page")) this.page = Number(params.get("page")) || 1
            if (params.has("per_page")) this.perPage = Number(params.get("per_page")) || 10

            this.fetchTickets()
        },
        methods: {
            async fetchCategories() {
                await useHttp().get("/api/categories", {}, {
                    onSuccess: (res) => {
                        this.categories = res?.data || []
                    },
                    onError: (err) => {
                        console.error("Failed to fetch categories:", err.message)
                        this.error = err
                    },
                })
            },
            async fetchTickets() {
                this.loading = true
                this.error = null

                // sync query params in the browser URL
                const newUrl = `${window.location.pathname}?${this.queryString}`
                window.history.replaceState({}, "", newUrl)

                await useHttp().get(`/api/tickets?${this.queryString}`, {}, {
                    onSuccess: (res) => {
                        this.tickets = res.data
                        this.perPage = res.meta.per_page
                        this.totalPages = res.meta.last_page
                    },
                    onError: (err) => {
                        this.error = err
                    },
                    onFinally: () => {
                        this.loading = false
                    },
                })
            },
            applyFilters() {
                this.page = 1
            },
            goToPage(p) {
                if (p < 1) p = 1
                if (p > this.totalPages) p = this.totalPages
                this.page = p
            },

            async classifyTicket(t) {
                if (!t || this.classifying[t.id]) return
                this.$set ? this.$set(this.classifying, t.id, true) : (this.classifying = { ...this.classifying, [t.id]: true })
                try {
                    await window.axios.post(`/api/tickets/${t.id}/classify`)
                    // Start polling until the classification fields update or timeout
                    this.startPolling(t.id)
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error(e)
                    alert("Failed to dispatch classification")
                    this.$set ? this.$set(this.classifying, t.id, false) : (this.classifying = { ...this.classifying, [t.id]: false })
                }
            },
            startPolling(id) {
                if (this.pollers[id]) clearInterval(this.pollers[id])
                let attempts = 0
                const maxAttempts = 20 // ~20s
                this.pollers[id] = setInterval(async () => {
                    attempts++
                    try {
                        const res = await window.axios.get(`/api/tickets/${id}`)
                        const updated = res.data?.data || res.data
                        const idx = this.tickets.findIndex(x => x.id === id)
                        if (idx !== -1) this.$set ? this.$set(this.tickets, idx, updated) : (this.tickets = this.tickets.map(x => x.id === id ? updated : x))
                        if (updated.classification) {
                            this.stopPolling(id)
                            this.$set ? this.$set(this.classifying, id, false) : (this.classifying = { ...this.classifying, [id]: false })
                        }
                    } catch (e) {
                        // ignore transient
                    }
                    if (attempts >= maxAttempts) {
                        this.stopPolling(id)
                        this.$set ? this.$set(this.classifying, id, false) : (this.classifying = { ...this.classifying, [id]: false })
                    }
                }, 1000)
            },
            stopPolling(id) {
                if (this.pollers[id]) {
                    clearInterval(this.pollers[id])
                    delete this.pollers[id]
                }
            },
            editNote(t) {
                this.editingTicket = t
                this.showNoteModal = true
            },
            closeNoteModal() {
                this.showNoteModal = false
                this.editingTicket = null
            },
        },
        beforeUnmount() {
            Object.keys(this.pollers).forEach(id => this.stopPolling(id))
        },
    }
</script>

<style>

    /***** Controls *****/
    .button--primary {
        background: #2f6fed;
        border-color: #2f6fed;
        color: #fff;
    }

    .input--search {
        max-width: 320px;
        padding: 6px 12px;
        height: 1.9rem;
    }

    .spinner {
        width: 12px;
        height: 12px;
        border: 2px solid #fff;
        border-right-color: transparent;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /***** Tickets *****/
    .tickets {
        max-width: 1200px;
        margin: 0 auto;
    }

    .tickets__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .tickets__filters {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 12px;
    }

    .tickets__loading, .tickets__empty {
        padding: 12px;
        height: 5rem;
        display: flex;
        justify-content: center;
        align-items: center;
        background: var(--color-bg-panel);
        color: var(--color-text-body);
    }

    .tickets__actions {
        display: flex;
        gap: 8px;
    }

    /***** Table *****/
    .ticket-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ticket-table__th, .ticket-table__td {
        text-align: left;
        color: var(--color-text-heading);
        border-bottom: 1px solid var(--color-text-body);
        padding: 8px;
        vertical-align: top;
    }

    .ticket-table__actions {
        text-align: right;
    }

    .ticket-table__subject {
        font-weight: 600;
        color: var(--color-text-body);
    }

    .ticket-table__meta {
        font-size: 12px;
        color: #777;
    }

    /***** Badges *****/
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--color-bg-panel-100);
        border: 1px solid #c7d2fe;
        color: var(--color-text-body);
        padding: 2px 6px;
        border-radius: 999px;
    }

    .badge__dot {
        display: inline-flex;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--color-bg-panel-200);
        color: #fff;
        font-size: 10px;
        align-items: center;
        justify-content: center;
    }

    .note-badge {
        display: inline-block;
        background: #ffe8a3;
        color: #7a5a00;
        border: 1px solid #ffd46b;
        border-radius: 6px;
        padding: 2px 6px;
        font-size: 12px;
    }

    /***** Pagination *****/
    .pagination {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
        margin: 12px;
    }

    .pagination__info {
        color: #555;
    }

    .ticket-table__wrapper {
        padding: 1rem;
        background: var(--color-bg-panel);
        color: var(--color-text-body);
        border-radius: 8px;
        margin-bottom: 2rem;
    }
</style>
