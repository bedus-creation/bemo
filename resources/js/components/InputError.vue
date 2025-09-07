<template>
    <span v-if="message" class="input-error text--small" aria-hidden="true">
        {{ message }}
    </span>
</template>
<script>
    export default {
        props: {
            message: {
                type: String,
                default: null,
            },
        },
    }
</script>
<style>
    .input-error {
        color: red;
    }
</style>
