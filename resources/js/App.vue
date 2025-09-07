<template>
    <header class="app__header">
        <h1 class="app__title">
            <router-link to="/" class="app__title-link">
                <img
                    src="https://assets.bemoacademicconsulting.com/files/AR8wdupQbJbHsczmjaMMjGId3cNRjmPLolORynZB.png"
                    alt="Bemo"
                    class="app__logo"/>
            </router-link>
        </h1>
        <nav class="nav">
            <router-link class="nav__link" to="/dashboard">Dashboard</router-link>
            <router-link class="nav__link" to="/tickets">Tickets</router-link>
        </nav>
    </header>
    <main class="app">
        <section class="app__content">
            <router-view></router-view>
        </section>
    </main>
</template>
<script>
    export default {
        name: "App",
        data() {
            return {
                title: "Bemo",
            }
        },
    }
</script>
