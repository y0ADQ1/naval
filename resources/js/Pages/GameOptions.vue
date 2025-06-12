<template>
    <div class="min-h-screen bg-blue-900 text-white p-6">
        <Layout>
            <h1 class="text-3xl font-bold mb-6">Batalla Naval - Opciones</h1>
            <div class="grid grap-4 max-w-md mx-auto">
                <button @click="createGame" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105">
                    Crear Partida
                </button>
                <button @click="joinGame" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105">
                    Unirse a una Partida
                </button>
                <button @click="viewHistory" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105">
                    Ver Historial de Partidas
                </button>
            </div>
        </Layout>
    </div>
</template>

<script>
import Layout from '../Layouts/Layout.vue';
import { Inertia } from '@inertiajs/inertia';

export default {
    components: { Layout },
    methods: {
        createGame() {
            Inertia.get('/games/create');
        },
        joinGame() {
            Inertia.get('/games');
        },
        viewHistory() {
            Inertia.get('/games/history');
        }
    },
};
</script>