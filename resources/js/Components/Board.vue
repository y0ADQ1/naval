<template>
    <div class="grid grid-cols-8  gap-1 bg-gray-800 p-2 rounded shadow-md">
        <div v-for="(row, rowIndex) in board" :key="rowIndex" class="flex flex-col">
            <div v-for="(cell, colIndex) in row" :key="colIndex" :class="{'bg-blue-500': cell === 1 && isOwnBoard,
                'bg-red-500': cell === 'hit',
                'bg-gray-300': cell === 'miss',
                'bg-gray-600': !isOwnBoard && cell !== 'hit' && cell !== 'miss',
            }" 
            class="w-10 h-10 border border-gray-600" @click="!isOwnBoard && cell !== 'hit' && cell !== 'miss' ? $emit('move', { x: rowIndex, y: colIndex }) : null">

            </div>

        </div>
    </div>

</template>

<script>
export default {
    props: {
        board: Array,
        isOwnBoard: Boolean,
    },
    emits: ['move'],
};
</script>