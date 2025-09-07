<template>
    <div v-if="show" class="modal" @click.self="close">
        <div class="modal__dialog">
            <h2 class="modal__title">New Ticket</h2>
            <form @submit.prevent="submitNewTicket">
                <div>
                    <label class="form__label">
                        Subject
                        <input
                            v-model="form.subject"
                            class="input"
                            @input="form.validate('subject')"/>
                        <InputError :message="form.errors.subject"/>
                    </label>
                </div>
                <div>
                    <label class="form__label">Body
                        <textarea
                            v-model="form.body"
                            class="textarea"
                            rows="5"
                            @input="form.validate('body')">
                    </textarea>
                        <InputError :message="form.errors.body"/>
                    </label>
                </div>
                <div class="modal__actions">
                    <button type="button" class="button" @click="close">Cancel</button>
                    <button type="submit" class="button button--primary" :disabled="form.processing || form.hasErrors">
                        {{ form.processing ? "Saving…" : "Create" }}
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
        props: {
            show: {
                type: Boolean,
                required: true,
            },
        },
        components: { InputError },
        data() {
            return {
                form: useForm({
                    subject: "",
                    body: "",
                }, {
                    schema: object({
                        subject: string().required().max(255).label("Subject"),
                        body: string().required().max(1000).label("Body"),
                    }),
                }),
            }
        },
        methods: {
            async submitNewTicket() {
                await this.form.validate()
                if (this.form.hasErrors) {
                    return
                }

                this.form.post("/api/tickets", {
                    onSuccess: () => {
                        this.clearAndReset()
                        this.$emit("success")
                    },
                    onError: () => {
                        alert("Failed to create ticket")
                    },
                })
            },

            clearAndReset() {
                this.form.clearErrors()
                this.form.reset()
            },

            close() {
                this.clearAndReset()
                this.$emit("close")
            },
        },
    }
</script>
