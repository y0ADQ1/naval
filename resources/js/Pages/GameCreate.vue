<template>
    <div class="min-h-screen bg-blue-900 text-white p-6">
        <Layout>
            <h1 class="text-3xl font-bold mb-6">Crear una nueva partida</h1>
            <form @submit.prevent="createGame" class="max-w-md mx-auto">
                <div class="mb-4">
                    <label class="block text-gray-200 mb-2">Nombre del juego</label>
                    <input v-model="form.name" type="text" class="w-full bg-gray-800 text-white border border-gray-600 rounded p-6 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                           placeholder="Ej: Batalla de pendejos">
                </div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded shadow-md transition duration-200 w-full">
                    Crear Juego

                </button>
            </form>
        </Layout>
    </div>
</template>

<script>
import Layout from '../Layouts/Layout.vue';
import { Inertia } from '@inertiajs/inertia';

export default {
    components: { Layout },
    data() {
        return {
            form: {
                name: '',
            },
        };
    },
    methods: {
        createGame() {
            Inertia.post('/games', this.form);
        },
    },
};
</script>