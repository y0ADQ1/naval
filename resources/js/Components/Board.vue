<template>
  <div class="flex flex-col">
    <!-- Header Row for X coordinates -->
    <div class="grid grid-cols-8 gap-1 mb-1 ml-10">
      <div
        v-for="x in 8"
        :key="'x-' + x"
        class="w-10 h-6 flex items-center justify-center text-gray-200 font-semibold text-sm"
      >
        X{{ x - 1 }}
      </div>
    </div>
    <div class="flex">
      <!-- Left Column for Y coordinates -->
      <div class="flex flex-col mr-1">
        <div
          v-for="y in [0, 1, 2, 3, 4, 5, 6, 7]"
          :key="'y-' + y"
          class="w-6 h-10 flex items-center justify-center text-gray-200 font-semibold text-sm"
        >
          Y{{ y }}
        </div>
      </div>
      <!-- Board Grid -->
      <div class="grid grid-cols-8 gap-1">
        <div
          v-for="(row, y) in board"
          :key="'row-' + y"
          class="flex flex-col"
        >
          <div
            v-for="(cell, x) in row"
            :key="'cell-' + x"
            class="w-10 h-10 flex items-center justify-center border border-gray-600 text-xs"
            :class="getCellClass(cell)"
            @click="!isOwnBoard && !cell ? $emit('move', { x, y }) : null"
          >
            <span v-if="cell === 'hit'">X({{ x }},{{ y }})</span>
            <span v-else-if="cell === 'miss'">O({{ x }},{{ y }})</span>
            <span v-else-if="isOwnBoard && cell === 1">■</span>
          </div>
        </div>
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
        return {
          'bg-blue-500 cursor-default text-white': cell === 1,
          'bg-gray-700 cursor-default': cell === 0 || cell === 'hit' || cell === 'miss',
        };
      }
      return {
        'bg-red-500 text-white': cell === 'hit',
        'bg-gray-300 text-gray-800': cell === 'miss',
        'bg-gray-700 cursor-pointer hover:bg-gray-600 text-gray-200': !cell,
      };
    },
  },
};
</script>