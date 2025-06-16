<template>
    <div class="mt-6 bg-gray-800 p-4 rounded shadow-md">
        <h2 class="text-xl font-semibold mb-2 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            Historial de Movimientos
        </h2>
        <div v-if="moves.length" class="space-y-2 max-h-60 overflow-y-auto pr-2">
            <div v-for="move in sortedMoves" :key="move.id"
                class="p-2 rounded mb-2 border-l-4 flex items-center"
                :class="move.result === 'hit' ? 'bg-red-900 bg-opacity-30 border-red-500' : 'bg-blue-900 bg-opacity-20 border-blue-500'">
                <div class="w-2 h-2 rounded-full mr-2" :class="move.result === 'hit' ? 'bg-red-500' : 'bg-blue-500'"></div>
                <div>
                    <span class="font-bold text-gray-200">{{ move.player?.name || 'Jugador' }}</span>
                    <span class="text-gray-300"> disparó en ({{ move.x }}, {{ move.y }}): </span>
                    <span :class="move.result === 'hit' ? 'text-red-400 font-bold' : 'text-blue-400'">
                        {{ move.result === 'hit' ? '¡Impacto!' : 'Agua'}}
                    </span>
                    <div class="text-xs text-gray-400">
                        {{ formatDate(move.created_at) }}
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="text-gray-400 italic"> Aún no hay movimientos registrados</p>
    </div>
</template>

<script>
export default {
    props: {
        moves: Array,
    },

    computed: {
        sortedMoves() {
            // Ordenar los movimientos por fecha de creación (más recientes primero)
            return [...this.moves].sort((a, b) => {
                return new Date(b.created_at) - new Date(a.created_at);
            });
        }
    },

    methods: {
        formatDate(dateString) {
            if (!dateString) return '';

            const date = new Date(dateString);
            return date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
};
</script>
