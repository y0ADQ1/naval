<template>
    <div class="min-h-screen bg-blue-900 text-white p-6">
        <Layout>
            <h1 class="text-3xl font-bold mb-6">Historial de partidas</h1>
        </Layout>
        <div v-if="games.length" class="grid gap-4">
            <div v-for="game in games" :key="game.id" class="bg-gray-800 p-4 rounded shadow-md">
                <p>Juego #{{ game.id }} - Estado: {{ game.status }}</p>
                <p>
                    Resultado:
                    <span :class="game.winner_id === auth.user.id ? 'text-green-400' : 'text-red-400'">
                        {{ game.winner_id === auth.user.id ? 'Ganaste' : game.winner_id ? 'Perdiste' : 'Sin terminar' }}
                    </span>
                </p>
                <p> Fecha: {{ new Date(game.created_at).toLocalDateString() }}</p>
            </div>
        </div>
        <p v-else class="text-gray-400">Juega pinche culo, que no tienes partidas jugadas xd</p>
    </div>
</template>

<script>
import Layout from '../Layouts/Layout.vue';

export default {
    components: { Layout },
    props: {
        games: Array,
        auth: Object,
    },
};
</script>