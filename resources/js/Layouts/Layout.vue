<!-- resources/js/Layouts/Layout.vue -->
<template>
    <div class="min-h-screen bg-blue-900">
        <nav class="bg-gray-800 p-4 shadow-md">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <button
                    @click="goToGames"
                    class="text-2xl font-bold text-white hover:text-gray-300 transition duration-200 focus:outline-none"
                >
                    Batalla Naval
                </button>
                <div class="space-x-4">
                    <button @click="goToStats" class="text-gray-200 hover:text-white transition duration-200">
                        Estadísticas
                    </button>
                    <button @click="logout" class="text-gray-200 hover:text-white transition duration-200">
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </nav>
        <main class="max-w-7xl mx-auto py-6">
            <slot/>
        </main>
    </div>
</template>

<script>
import { Inertia } from '@inertiajs/inertia';

export default {
    methods: {
        goToGames() {
            Inertia.get('/games');
        },
        goToStats() {
            Inertia.get('/games/stats');
        },
        logout() {
            Inertia.post('/logout');
        }
    }
};
</script>