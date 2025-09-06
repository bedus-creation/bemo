<template>
    <div v-if="show" class="modal" @click.self="close()">
        <div class="modal__dialog">
            <h2 class="modal__title">Edit Internal Note</h2>
            <form @submit.prevent="submitNote">
                <textarea v-model="noteDraft" class="textarea" rows="6" placeholder="Enter note…"></textarea>
                <div class="modal__actions">
                    <button type="button" class="button" @click="close()">Cancel</button>
                    <button type="submit" class="button button--primary" :disabled="savingNote">
                        {{ savingNote ? "Saving…" : "Save Note" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            show: {
                type: Boolean,
                required: true,
            },
            ticket: {
                type: Object,
            },
        },
        data() {
            return {
                noteDraft: "",
                savingNote: false,
            }
        },
        methods: {
            async submitNote() {
                if (!this.ticket) return
                this.savingNote = true
                try {
                    const res = await window.axios.patch(`/api/tickets/${this.ticket.id}`, { note: this.noteDraft })
                    const updated = res.data?.data || res.data
                    // const idx = this.tickets.findIndex(x => x.id === this.ticket.id)
                    // if (idx !== -1) this.$set ? this.$set(this.tickets, idx, updated) : (this.tickets = this.tickets.map(x => x.id === this.editingTicket.id ? updated : x))
                    this.close()
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error(e)
                    alert("Failed to save note")
                } finally {
                    this.savingNote = false
                }
            },
            close() {
                this.$emit("close")
            },
        },
    }
</script>
