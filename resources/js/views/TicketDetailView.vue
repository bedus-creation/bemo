<template>
    <section class="ticket-detail">
        <header class="ticket-detail__header">
            <h1 class="ticket-detail__title">Ticket</h1>
            <div class="ticket-detail__actions">
                <router-link class="link--button text--small" to="/tickets">Go to Tickets</router-link>
                <button
                    class="button button--primary"
                    :disabled="classifying || ticket?.classification"
                    @click="runClassification">
                    <span v-if="classifying" class="spinner" aria-hidden="true"></span>
                    {{ classifying ? "Classifying…" : "Run Classification" }}
                </button>
            </div>
        </header>

        <div v-if="loading" class="ticket-detail__loading">Loading…</div>
        <div v-else-if="error" class="ticket-detail__error">{{ error }}</div>
        <div v-else-if="ticket" class="ticket-detail__content">
            <div class="ticket-detail__info">
                <div class="ticket-detail__section">
                    <label class="ticket-detail__label">Subject</label>
                    <div class="ticket-detail__body">{{ ticket.subject }}</div>
                </div>

                <div class="ticket-detail__section">
                    <label class="ticket-detail__label">Body</label>
                    <pre class="ticket-detail__body">{{ ticket.body }}</pre>
                </div>

                <div class="ticket-detail__grid">
                    <div class="ticket-detail__section">
                        <label class="ticket-detail__label">Category</label>
                        <select class="select ticket-detail__body" v-model="categoryDraft" @change="saveCategory">
                            <option value="">—</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <small class="ticket-detail__hint">Changing the dropdown saves immediately.</small>
                    </div>
                </div>
            </div>

            <div class="ticket-detail__grid ticket-detail__ai">
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

            <div class="ticket-detail__section ticket-detail__section--wide ticket-detail__internal_notes">
                <form @submit.prevent="saveNote">
                    <label class="ticket-detail__label">Internal Note</label>
                    <textarea class="textarea"
                              rows="6"
                              v-model="noteForm.note"
                              placeholder="Add an internal note…">
                </textarea>
                    <div class="ticket-detail__note-actions">
                        <button class="button"
                                type="submit"
                                :disabled="noteForm.processing || noteForm.hasErrors">
                            {{ noteForm.processing ? "Saving…" : "Save Note" }}
                        </button>
                        <InputError :message="noteForm.errors.note"/>
                        <span v-if="savedAt" class="ticket-detail__muted">Saved at {{ savedAt }}</span>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>

<script>
    import { useForm } from "formjs-vue2"
    import { object, string } from "yup"
    import InputError from "../components/InputError.vue"
    import { useHttp } from "../composables/useHttp.js"
    export default {
        name: "TicketDetailView",
        components: { InputError },
        data() {
            return {
                loading: false,
                error: null,
                ticket: null,
                classifying: false,
                poller: null,
                categories: [],
                categoryDraft: "",

                noteForm: useForm({
                    note: "",
                }, {
                    schema: object({
                        note: string().required().max(1000).label("Internal note"),
                    }),
                }),
                savedAt: "",
            }
        },
        created() {
            this.fetchCategories()
            this.fetchTicket()
        },
        methods: {
            async fetchCategories() {
                await useHttp().get("/api/categories", {}, {
                    onSuccess: (data) => {
                        this.categories = data?.data || []
                    },
                })
            },
            async fetchTicket() {
                this.loading = true
                this.error = null

                const id = this.$route.params.id
                await useHttp().get(`/api/tickets/${id}`, {}, {
                    onSuccess: (data) => {
                        this.ticket = data.data
                        this.categoryDraft = this.ticket.category?.id || ""
                        this.noteForm.note = this.ticket.note || ""
                    },
                    onError: (error) => {
                        this.error = error?.response?.data?.message || "Failed to load ticket"
                    },
                    onFinally: () => {
                        this.loading = false
                    },
                })
            },
            async saveCategory() {
                if (!this.ticket) return

                await useHttp().put(`/api/tickets/${this.ticket.id}`, { category: this.categoryDraft || null }, {
                    onSuccess: () => {
                        // Alert something
                    },
                    onError: () => {
                        this.categoryDraft = this.ticket.category?.id || ""
                    },
                })
            },
            async saveNote() {
                await this.noteForm.validate()
                if (this.noteForm.hasErrors) {
                    return
                }

                this.noteForm.put(`/api/tickets/${this.ticket.id}`, {
                    onSuccess: () => {
                        this.savedAt = new Date().toLocaleTimeString()
                    },
                    onError: () => {
                        alert("Failed to update note")
                        this.noteForm.reset()
                    },
                })
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
                        if (t.classification) {
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
        color: var(--color-text-heading);
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
        color: var(--color-text-heading);
    }

    .ticket-detail__subject {
        background: #fafafa;
        border: 1px solid #eee;
        padding: 8px;
        border-radius: 4px;
    }

    .ticket-detail__body {
        background: var(--color-bg-panel-100);
        border: 1px solid var(--color-text-body);
        color: var(--color-text-body);
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
        background: var(--color-bg-panel-100);
        border: 1px solid var(--color-text-body);
        color: var(--color-text-body);
        padding: 8px;
        border-radius: 4px;
        min-height: 36px;
        cursor: not-allowed;
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
        border: 1px solid var(--color-text-heading);
        background: var(--color-bg-panel-100);
        color: var(--color-text-body);
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

    .ticket-detail__info {
        background: var(--color-bg-panel);
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 2rem;
    }

    .ticket-detail__ai {
        background: var(--color-bg-panel);
        padding: 1rem;
        border-radius: 4px;
    }

    .ticket-detail__internal_notes {
        background: var(--color-bg-panel);
        padding: 1rem;
        border-radius: 4px;
        margin-top: 2rem;
    }
</style>
