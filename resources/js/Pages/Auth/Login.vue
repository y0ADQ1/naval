<template>
  <div class="min-h-screen bg-blue-900 text-white flex items-center justify-center">
    <div class="max-w-md w-full p-6 bg-gray-800 rounded-lg shadow-md">
      <h1 class="text-3xl font-bold mb-6 text-center">Iniciar Sesión</h1>
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-gray-200 mb-2" for="email">Correo Electrónico</label>
          <input
            v-model="form.email"
            type="email"
            id="email"
            class="w-full bg-gray-700 text-white border border-gray-600 rounded p-2 focus:outline-none focus:ring-2 focus:ring-emerald-600"
            required
          />
        </div>
        <div class="mb-4">
          <label class="block text-gray-200 mb-2" for="password">Contraseña</label>
          <input
            v-model="form.password"
            type="password"
            id="password"
            class="w-full bg-gray-700 text-white border border-gray-600 rounded p-2 focus:outline-none focus:ring-2 focus:ring-emerald-600"
            required
          />
        </div>
        <div class="flex items-center justify-between mb-4">
          <label class="flex items-center text-gray-200">
            <input v-model="form.remember" type="checkbox" class="mr-2" />
            Recordarme
          </label>
          <a href="/forgot-password" class="text-emerald-400 hover:text-emerald-500">¿Olvidaste tu contraseña?</a>
        </div>
        <button
          type="submit"
          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded shadow-md transition"
        >
          Iniciar Sesión
        </button>
      </form>
      <p class="mt-4 text-center">
        <a href="/register" class="text-emerald-400 hover:text-emerald-500">¿No tienes cuenta? Regístrate</a>
      </p>
    </div>
  </div>
</template>

<script>
import { Inertia } from '@inertiajs/inertia';

export default {
  data() {
    return {
      form: {
        email: '',
        password: '',
        remember: false,
      },
    };
  },
  methods: {
    submit() {
      Inertia.post('/login', this.form);
    },
  },
};
</script>