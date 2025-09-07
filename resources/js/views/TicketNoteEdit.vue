<template>
    <div v-if="show" class="modal" @click.self="close()">
        <div class="modal__dialog">
            <h2 class="modal__title">Edit Internal Note</h2>
            <form @submit.prevent="submitNote">
                <textarea
                    v-model="form.note"
                    class="textarea"
                    rows="6" placeholder="Enter note…"
                    @input="form.validate('note')">
                </textarea>
                <InputError :message="form.errors.note"/>
                <div class="modal__actions">
                    <button type="button" class="button" @click="close()">Cancel</button>
                    <button type="submit" class="button button--primary" :disabled="form.processing || form.hasErrors">
                        {{ form.processing ? "Saving…" : "Save Note" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
    import { useForm } from "formjs-vue2"
    import { object, string } from "yup"
    import InputError from "../components/InputError.vue"
    export default {
        components: { InputError },
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
                form: useForm({
                    note: "",
                }, {
                    schema: object({
                        note: string().required().max(255).label("Note"),
                    }),
                }),
            }
        },
        watch: {
            ticket(newVal) {
                if (newVal) {
                    this.form.note = newVal.note || ""
                }
            },
        },
        methods: {
            async submitNote() {
                await this.form.validate()
                if (this.form.hasErrors) {
                    return
                }

                this.form.put(`/api/tickets/${this.ticket.id}`, {
                    onSuccess: () => {
                        this.$emit("success")
                    },
                })
            },
            close() {
                this.form.clearErrors()
                this.form.reset()
                this.$emit("close")
            },
        },
    }
</script>
