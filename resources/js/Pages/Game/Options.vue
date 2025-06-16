<template>
    <Layout>
        <div class="bg-gray-800 p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-white mb-4">
                Opciones de juego
            </h2>
            <div class="mb-6">
                <button @click="createGame" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105">
                    Crear partida
                </button>
            </div>
            <h3 class="text-xl font-semibold text-gray-200 mb-2">
                Partidas disponibles
            </h3>
            <div v-if="games.length" class="space-y-4">
                <div v-for="game in games" :key="game.id" class="flex justify-between items-center bg-gray-700 p-4 rounded-lg">
                    <span>Juego #{{ game.id }} - Creado por {{ game.player1.name }}</span>
                    <button @click="joinGame(game.id)" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105">
                        Unirse
                    </button>
                </div>
            </div>
            <p v-else class="text-gray-400 text-center mt-4">
                No hay juegos disponibles en este momento.
            </p>

        </div>
    </Layout>
</template>

<script>
import { Inertia } from '@inertiajs/inertia';
import Layout from '@/Layouts/Layout.vue';

export default {
    components: { Layout },
    props: {
        games: Array,
    },
    methods: {
        createGame() {
        Inertia.post('/games');          // sin onSuccess ni visit
        },

        joinGame(gameId) {
            Inertia.post(`/games/${gameId}/join`, {}, {
                onSuccess: () => {
                    Inertia.visit(`/games/${gameId}/play`);
                },
            });
        },
    },
};
</script>