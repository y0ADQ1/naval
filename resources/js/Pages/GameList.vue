<template>
    <div class="min-h-screen bg-blue-900 text-white p-6">
        <Layout>
            <h1 class="text-3xl font-bold mb-6"> Batalla Naval - Juegos Disponibles</h1>

            <div class="mb-4">
                <button @click="goToCreateGame"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded shadow-md transition duration-200">
                        Crear Nuevo Juego
                </button>
            </div>
            <div v-if="games.length" class="grid gap-4">
                <div v-for="game in games" :key="game.id" class="bg-gray-800 p-4 rounded shadow-md flex justify-between items-center">

                    <span> Juego #{{ game.id }} - {{ game.status }}</span>
                    <button v-if="game.status === 'waiting'" @click="joinGame(game.id)" class="bg-emerald-600 hover:bg-emerald-700 text-white py-1 px-3 rounded">Unirse</button>
                </div>
            </div>
            <p v-else class="text-gray-400">No hay partidas disponibles</p>
        </Layout>
    </div>
</template>

<script>
import Layout from '../Layouts/Layout.vue';
import { Inertia } from '@inertiajs/inertia';

export default {
    components: { Layout },
    props: {
        games: Array,
    },
    methods: {
        goToCreateGame() {
            Inertia.get('/games/create');
        },
        joinGame(gameId) {
            Inertia.post('/games/${gameId}/join');
        }
    }
}
</script>