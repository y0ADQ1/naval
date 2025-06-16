<template>
    <div class="grid grid-cols-8 gap-1">
        <div
            v-for="(row, y) in board"
            :key="y"
            class="flex flex-col"
        >
            <div
                v-for="(cell, x) in row"
                :key="x"
                class="w-10 h-10 flex items-center justify-center border border-gray-600"
                :class="getCellClass(cell)"
                @click="!isOwnBoard && !cell ? $emit('move', { x, y }) : null"
            >
                {{ cell === 'hit' ? 'X' : cell === 'miss' ? 'O' : '' }}
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

    methods: {
        getCellClass(cell) {
            if (this.isOwnBoard) {
                return cell === 1 ? 'bg-blue-500 cursor-default' : 'bg-gray-700 cursor-default';
            }
            return {
                'bg-red-500': cell === 'hit',
                'bg-gray-300': cell === 'miss',
                'bg-gray-700 cursor-pointer hover:bg-gray-600': !cell,
            };
        },
    },
};
</script>