<template>
    <section class="ticket-detail">
        <header class="ticket-detail__header">
            <h1 class="ticket-detail__title">Ticket</h1>
            <div class="ticket-detail__actions">
                <router-link class="button" to="/tickets">Back to list</router-link>
                <button class="button button--primary" :disabled="classifying || ticket?.classification" @click="runClassification">
                    <span v-if="classifying" class="spinner" aria-hidden="true"></span>
                    <span>{{ classifying ? "Classifying…" : "Run Classification" }}</span>
                </button>
            </div>
        </header>

        <div v-if="loading" class="ticket-detail__loading">Loading…</div>
        <div v-else-if="error" class="ticket-detail__error">{{ error }}</div>
        <div v-else-if="ticket" class="ticket-detail__content">
            <div class="ticket-detail__section">
                <label class="ticket-detail__label">Subject</label>
                <div class="ticket-detail__subject">{{ ticket.subject }}</div>
            </div>

            <div class="ticket-detail__section">
                <label class="ticket-detail__label">Body</label>
                <pre class="ticket-detail__body">{{ ticket.body }}</pre>
            </div>

            <div class="ticket-detail__grid">
                <div class="ticket-detail__section">
                    <label class="ticket-detail__label">Category</label>
                    <select class="select" v-model="categoryDraft" @change="saveCategory">
                        <option value="">—</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <small class="ticket-detail__hint">Changing the dropdown saves immediately.</small>
                </div>
            </div>
            <div class="ticket-detail__grid">
                <div class="ticket-detail__section">
                    <label class="ticket-detail__label">AI Category</label>
                    <div class="ticket-detail__readonly">{{ ticket.classification?.category?.name || "-" }}</div>
                </div>

                <div class="ticket-detail__section">
                    <label class="ticket-detail__label">Confidence</label>
                    <div class="ticket-detail__readonly">{{ ticket.classification?.confidence || "-" }}</div>
                </div>

                <div class="ticket-detail__section ticket-detail__section--wide">
                    <label class="ticket-detail__label">AI Explanation</label>
                    <div class="ticket-detail__readonly ticket-detail__explanation" :class="{ 'ticket-detail__readonly--empty': !ticket.classification_explanation }">
                        {{ ticket.classification?.explanation || "—" }}
                    </div>
                </div>
            </div>

            <div class="ticket-detail__section ticket-detail__section--wide">
                <label class="ticket-detail__label">Internal Note</label>
                <textarea class="textarea" rows="6" v-model="noteDraft" placeholder="Add an internal note…"></textarea>
                <div class="ticket-detail__note-actions">
                    <button class="button" :disabled="savingNote || noteDraft === (ticket.note || '')" @click="saveNote">
                        {{ savingNote ? "Saving…" : "Save Note" }}
                    </button>
                    <span v-if="saveError" class="ticket-detail__error">{{ saveError }}</span>
                    <span v-if="savedAt" class="ticket-detail__muted">Saved at {{ savedAt }}</span>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    export default {
        name: "TicketDetailView",
        data() {
            return {
                loading: false,
                error: null,
                ticket: null,
                classifying: false,
                poller: null,
                categories: [],
                categoryDraft: "",
                noteDraft: "",
                savingNote: false,
                saveError: null,
                savedAt: "",
            }
        },
        computed: {
            confidenceText() {
                const c = this.ticket?.classification_confidence
                if (c === null || c === undefined) return "—"
                try { return Number(c).toFixed(2) } catch (_) { return String(c) }
            },
        },
        created() {
            this.fetchCategories()
            this.fetchTicket()
        },
        methods: {
            async fetchCategories() {
                const res = await fetch("/api/categories", {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                    },
                })

                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`)
                }

                const data = await res.json()
                this.categories = data?.data || data || []
            },
            async fetchTicket() {
                this.loading = true
                this.error = null
                try {
                    const id = this.$route.params.id
                    const res = await window.axios.get(`/api/tickets/${id}`)
                    const t = res.data?.data || res.data
                    this.ticket = t
                    this.categoryDraft = t.category?.id || ""
                    this.noteDraft = t.note || ""
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error(e)
                    this.error = "Failed to load ticket"
                } finally {
                    this.loading = false
                }
            },
            async saveCategory() {
                if (!this.ticket) return
                try {
                    const res = await window.axios.patch(`/api/tickets/${this.ticket.id}`, { category: this.categoryDraft || null })
                    this.ticket = res.data?.data || res.data
                } catch (e) {
                    alert("Failed to update category")
                    this.categoryDraft = this.ticket.category || ""
                }
            },
            async saveNote() {
                if (!this.ticket) return
                this.savingNote = true
                this.saveError = null
                try {
                    const res = await window.axios.patch(`/api/tickets/${this.ticket.id}`, { note: this.noteDraft || null })
                    this.ticket = res.data?.data || res.data
                    this.savedAt = new Date().toLocaleTimeString()
                } catch (e) {
                    this.saveError = "Failed to save note"
                } finally {
                    this.savingNote = false
                }
            },
            async runClassification() {
                if (!this.ticket || this.classifying) return
                this.classifying = true
                try {
                    await window.axios.post(`/api/tickets/${this.ticket.id}/classify`)
                    this.startPolling()
                } catch (e) {
                    alert("Failed to dispatch classification")
                    this.classifying = false
                }
            },
            startPolling() {
                if (this.poller) clearInterval(this.poller)
                let attempts = 0
                const maxAttempts = 20
                this.poller = setInterval(async () => {
                    attempts++
                    try {
                        const res = await window.axios.get(`/api/tickets/${this.ticket.id}`)
                        const t = res.data?.data || res.data
                        this.ticket = t
                        if (t.classification_confidence !== null && t.classification_confidence !== undefined) {
                            this.stopPolling()
                            this.classifying = false
                        }
                    } catch (e) {
                        // ignore transient
                    }
                    if (attempts >= maxAttempts) {
                        this.stopPolling()
                        this.classifying = false
                    }
                }, 1000)
            },
            stopPolling() {
                if (this.poller) {
                    clearInterval(this.poller)
                    this.poller = null
                }
            },
        },
        beforeUnmount() {
            this.stopPolling()
        },
    }
</script>

<style>
    .ticket-detail {
        max-width: 900px;
        margin: 0 auto;
        padding: 16px;
    }

    .ticket-detail__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .ticket-detail__title {
        margin: 0;
    }

    .ticket-detail__actions {
        display: flex;
        gap: 8px;
    }

    .ticket-detail__loading, .ticket-detail__error {
        padding: 12px;
        color: #555;
    }

    .ticket-detail__content {
        display: block;
    }

    .ticket-detail__section {
        margin-bottom: 12px;
    }

    .ticket-detail__section--wide {
        grid-column: 1 / -1;
    }

    .ticket-detail__label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .ticket-detail__subject {
        background: #fafafa;
        border: 1px solid #eee;
        padding: 8px;
        border-radius: 4px;
    }

    .ticket-detail__body {
        background: #fff;
        border: 1px solid #eee;
        padding: 8px;
        border-radius: 4px;
        white-space: pre-wrap;
    }

    .ticket-detail__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .ticket-detail__readonly {
        background: #fafafa;
        border: 1px solid #eee;
        padding: 8px;
        border-radius: 4px;
        min-height: 36px;
    }

    .ticket-detail__readonly--empty {
        color: #777;
    }

    .ticket-detail__explanation {
        white-space: pre-wrap;
    }

    .ticket-detail__note-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }

    .ticket-detail__hint {
        display: block;
        color: #777;
        margin-top: 4px;
    }

    .ticket-detail__muted {
        color: #777;
        font-size: 12px;
    }

    /* Reuse base controls from TicketsView */
    .button {
        background: #f0f0f0;
        border: 1px solid #ccc;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .button:disabled {
        opacity: 0.6;
        cursor: default;
    }

    .button--primary {
        background: #2f6fed;
        border-color: #2f6fed;
        color: #fff;
    }

    .select, .textarea {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 8px;
        width: 100%;
        box-sizing: border-box;
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
</style>
