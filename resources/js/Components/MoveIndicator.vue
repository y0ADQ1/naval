<template>
    <div class="mt-6 bg-gray-800 p-4 rounded shadow-md">
        <h2 class="text-xl font-semibold mb-2">Movimientos</h2>
        <div v-if="moves.length" class="space-y-2">
            <p v-for="move in moves" :key="move.id" class="text-gray-200">
                {{ move.user.name }} disparó en ({{ move.x }}, {{ move.y }}):
                <span :class="move.result === 'hit' ? 'text-red-500' : 'text-gray-300'">
                    {{ move.result === 'hit' ? '¡Impacto!' : 'Agua'}}
                </span>
            </p>
        </div>
        <p v-else class="text-gray-400"> Aun no hay movimientos aún</p>
    </div>
</template>

<script>
export default {
    props: {
        moves: Array,
    },
};
</script>