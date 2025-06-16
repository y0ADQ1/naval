<template>
  <div class="min-h-screen bg-blue-900 text-white flex items-center justify-center">
    <div class="text-center">
      <!-- Animación del barco -->
      <div class="relative w-32 h-32 mx-auto mb-6">
        <div class="boat-animation">
          <svg class="w-24 h-24 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v6l3-3m-3 3l-3-3m3 6v6m-9-3h18"></path>
          </svg>
        </div>
      </div>
      <h1 class="text-5xl font-bold mb-4">Batalla Naval</h1>
      <p class="text-gray-200 mb-6">¡Prepárate para hundir los barcos de tu enemigo!</p>
      <button
        @click="playNow"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105"
      >
        Jugar Ahora
      </button>
    </div>
  </div>
</template>

<style>
.boat-animation {
  animation: sway 3s ease-in-out infinite;
}
@keyframes sway {
  0% { transform: translateX(0); }
  50% { transform: translateX(20px); }
  100% { transform: translateX(0); }
}
</style>

<script>
import { Inertia } from '@inertiajs/inertia';

export default {
  props: {
    auth: Object,
  },
  methods: {
    playNow() {
      if (this.auth.user) {
        Inertia.get('/games');
      } else {
        Inertia.get('/login');
      }
    },
  },
};
</script>