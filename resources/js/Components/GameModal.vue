<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg max-w-4xl w-full">
      <!-- Table View -->
      <div v-if="!selectedGame">
        <h3 class="text-xl font-bold text-white mb-4">
          {{ title }}
        </h3>
        <table class="w-full text-gray-200 border-collapse">
          <thead>
            <tr class="bg-gray-700">
              <th class="p-2 border border-gray-600">Jugador 1</th>
              <th class="p-2 border border-gray-600">Jugador 2</th>
              <th class="p-2 border border-gray-600">Estado</th>
              <th class="p-2 border border-gray-600">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="game in games" :key="game.id" class="border border-gray-600">
              <td class="p-2">{{ game.player1?.name || 'Jugador 1' }}</td>
              <td class="p-2">{{ game.player2?.name || 'Jugador 2' }}</td>
              <td class="p-2 text-center">
                <span :class="isUserWinner(game) ? 'text-green-500' : 'text-red-500'">
                  {{ isUserWinner(game) ? 'Ganador' : 'Perdedor' }}
                </span>
              </td>
              <td class="p-2 text-center">
                <button
                  @click="showDetails(game)"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg transition transform hover:scale-105 flex items-center mx-auto"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  Ver detalles
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <button
          @click="$emit('close')"
          class="mt-4 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105"
        >
          Cerrar
        </button>
      </div>

      <!-- Detail View -->
      <div v-else>
        <h3 class="text-xl font-bold text-white mb-4">Detalles de la Partida #{{ selectedGame.id }}</h3>
        <div class="mb-6">
          <p class="text-gray-200 mb-2">
            <strong>Jugadores:</strong>
            {{ selectedGame.player1?.name || 'Jugador 1' }} vs {{ selectedGame.player2?.name || 'Jugador 2' }}
          </p>
          <p class="text-gray-200">
            <strong>Resultado:</strong>
            <span class="font-bold">
              {{ selectedGame.winner === selectedGame.player1?.id ? (selectedGame.player1?.name || 'Jugador 1') : (selectedGame.player2?.name || 'Jugador 2') }} 
              <span class="text-green-500">ganó</span>
            </span>
            <span class="mx-2">|</span>
            <span class="font-bold">
              {{ selectedGame.winner === selectedGame.player1?.id ? (selectedGame.player2?.name || 'Jugador 2') : (selectedGame.player1?.name || 'Jugador 1') }} 
              <span class="text-red-500">perdió</span>
            </span>
          </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <h4 class="text-lg font-semibold text-gray-200 mb-2">
              Tablero de {{ selectedGame.player1?.name || 'Jugador 1' }}
              <span :class="selectedGame.winner === selectedGame.player1?.id ? 'text-green-500' : 'text-red-500'">
                ({{ selectedGame.winner === selectedGame.player1?.id ? 'Ganador' : 'Perdedor' }})
              </span>
            </h4>
            <Board :board="player1Board" :is-own-board="true" />
          </div>
          <div>
            <h4 class="text-lg font-semibold text-gray-200 mb-2">
              Tablero de {{ selectedGame.player2?.name || 'Jugador 2' }}
              <span :class="selectedGame.winner === selectedGame.player2?.id ? 'text-green-500' : 'text-red-500'">
                ({{ selectedGame.winner === selectedGame.player2?.id ? 'Ganador' : 'Perdedor' }})
              </span>
            </h4>
            <Board :board="player2Board" :is-own-board="true" />
          </div>
        </div>
        <div class="mb-6">
          <button
            @click="showMoves = !showMoves"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105 flex items-center"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            {{ showMoves ? 'Ocultar Movimientos' : 'Ver Movimientos' }}
          </button>
          <div v-if="showMoves" class="mt-4 bg-gray-700 p-4 rounded-lg max-h-60 overflow-y-auto">
            <h4 class="text-lg font-semibold text-gray-200 mb-2">Movimientos de la Partida</h4>
            <div v-if="sortedMoves.length" class="space-y-2">
              <div
                v-for="move in sortedMoves"
                :key="move.id"
                class="p-2 rounded border-l-4"
                :class="move.result === 'hit' ? 'bg-red-900 bg-opacity-30 border-red-500' : 'bg-blue-900 bg-opacity-20 border-blue-500'"
              >
                <div class="flex items-center">
                  <div class="w-2 h-2 rounded-full mr-2" :class="move.result === 'hit' ? 'bg-red-500' : 'bg-blue-500'"></div>
                  <span class="font-bold text-gray-200">{{ move.player?.name || 'Jugador Desconocido' }}</span>
                  <span class="text-gray-300"> disparó en ({{ move.x }}, {{ move.y }}): </span>
                  <span :class="move.result === 'hit' ? 'text-red-400 font-bold' : 'text-blue-400'">
                    {{ move.result === 'hit' ? 'Impacto' : 'Agua' }}
                  </span>
                </div>
              </div>
            </div>
            <p v-else class="text-gray-400 italic">No hay movimientos registrados</p>
          </div>
        </div>
        <div class="flex justify-between">
          <button
            @click="selectedGame = null"
            class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105"
          >
            Volver
          </button>
          <button
            @click="$emit('close')"
            class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition transform hover:scale-105"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Board from './Board.vue';
import { usePage } from '@inertiajs/inertia-vue3';

export default {
  components: { Board },
  props: {
    games: Array,
    show: Boolean,
    filter: String, // 'all', 'won', 'lost'
  },
  setup() {
    const { props } = usePage();
    return { auth: props.auth };
  },
  data() {
    return {
      selectedGame: null,
      showMoves: false,
    };
  },
  computed: {
    title() {
      if (this.filter === 'won') return 'Partidas Ganadas';
      if (this.filter === 'lost') return 'Partidas Perdidas';
      return 'Todas las Partidas';
    },
    player1Board() {
      if (!this.selectedGame) return Array(8).fill().map(() => Array(8).fill(0));
      const board = this.selectedGame.boards?.find(b => b.player_id === this.selectedGame.player1?.id);
      return this.computeFinalBoard(board, this.selectedGame.player2?.id);
    },
    player2Board() {
      if (!this.selectedGame) return Array(8).fill().map(() => Array(8).fill(0));
      const board = this.selectedGame.boards?.find(b => b.player_id === this.selectedGame.player2?.id);
      return this.computeFinalBoard(board, this.selectedGame.player1?.id);
    },
    sortedMoves() {
      if (!this.selectedGame?.moves) return [];
      return [...this.selectedGame.moves].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    },
  },
  methods: {
    showDetails(game) {
      this.selectedGame = game;
      this.showMoves = false; // Reset moves visibility when selecting a new game
    },
    isUserWinner(game) {
      const userId = String(this.auth?.user?.id || '');
      const winnerId = String(game.winner || '');
      console.log(`Game ID: ${game.id}, User ID: ${userId}, Winner ID: ${winnerId}, Is Winner: ${userId === winnerId}`);
      return userId === winnerId;
    },
    computeFinalBoard(board, opponentId) {
      let finalBoard = Array(8).fill().map(() => Array(8).fill(0));
      if (board?.grid) {
        try {
          finalBoard = typeof board.grid === 'string' ? JSON.parse(board.grid) : JSON.parse(JSON.stringify(board.grid));
        } catch (e) {
          console.error('Error parsing board.grid:', e);
        }
      }
      if (this.selectedGame?.moves && opponentId) {
        this.selectedGame.moves
          .filter(move => move.player_id === opponentId)
          .forEach(move => {
            if (typeof move.y === 'number' && typeof move.x === 'number' && move.result) {
              finalBoard[move.y][move.x] = move.result; // 'hit' or 'miss'
            }
          });
      }
      return finalBoard;
    },
  },
};
</script>