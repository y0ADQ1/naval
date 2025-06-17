<template>
  <Layout>
    <div class="bg-gray-800 p-6 rounded-lg shadow-md">
      <!-- Encabezado -->
      <h2 class="text-2xl font-bold text-white mb-4">
        Partida # {{ localGame.id }} ‑ Sala de: {{ localGame.player1.name }}
      </h2>

      <!-- Mensajes de estado -->
      <div v-if="localGame.status === 'waiting'" class="text-gray-200 mb-4">
        Esperando a que otro jugador se una... <span v-if="joinTimeoutMessage" class="text-red-500">{{ joinTimeoutMessage }}</span>
      </div>

      <div
        v-else-if="localGame.status === 'finished'"
        class="text-2xl font-bold mb-4"
        :class="localGame.winner === auth.user.id ? 'text-green-500' : 'text-red-500'"
      >
        {{ localGame.winner === auth.user.id ? '¡Has ganado!' : 'Has perdido.' }}
        <span v-if="localGame.status_reason" class="text-sm ml-2"> ({{ localGame.status_reason }})</span>
      </div>

      <!-- Indicador de turno -->
      <div v-if="localGame.status === 'active'" class="mb-4 p-3 rounded-md flex items-center"
        :class="isMyTurn ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-200'">
        <div class="w-3 h-3 rounded-full mr-2"
          :class="isMyTurn ? 'bg-white animate-pulse' : 'bg-gray-400'"></div>
        <span class="font-bold">
          {{ isMyTurn ? '¡Es tu turno!' : 'Esperando el movimiento del oponente...' }}
        </span>
      </div>

      <!-- Botón para abandonar partida -->
      <div v-if="localGame.status === 'active'" class="mb-4">
        <button
          @click="abandonGame"
          class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
        >
          Abandonar partida
        </button>
      </div>

      <!-- Mensaje de resultado -->
      <div v-if="lastMoveResult" class="mb-4 p-3 rounded-md" :class="lastMoveResult === 'hit' ? 'bg-green-700 text-white' : 'bg-blue-700 text-white'">
        <span class="font-bold">Último movimiento:</span> {{ lastMoveResult === 'hit' ? '¡Impacto!' : 'Agua' }} en ({{ lastMoveX }}, {{ lastMoveY }})
        <span v-if="moveConfirmed" class="ml-2 text-sm bg-gray-800 text-white px-2 py-1 rounded">✓ Confirmado</span>
      </div>

      <!-- Notificación de confirmación -->
      <div v-if="moveConfirmed" class="mb-4 p-3 bg-green-600 text-white rounded-md">
        <span class="font-bold">✓ Movimiento registrado correctamente</span> y guardado en la base de datos.
      </div>

      <!-- Mensaje de error o timeout -->
      <div v-if="errorMessage" class="mb-4 p-3 bg-red-600 text-white rounded-md">
        <span class="font-bold">Error:</span> {{ errorMessage }}
      </div>
      <div v-if="timeoutMessage" class="mb-4 p-3 bg-yellow-700 text-white rounded-md">
        <span class="font-bold">Aviso:</span> {{ timeoutMessage }}
      </div>

      <!-- Tableros -->
      <div v-if="localGame.status === 'active'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tu tablero -->
        <div>
          <h3 class="text-xl font-semibold text-gray-200 mb-2">Tu tablero</h3>
          <Board :board="ownBoard.grid" :is-own-board="true" />
        </div>

        <!-- Tablero del enemigo -->
        <div>
          <h3 class="text-xl font-semibold text-gray-200 mb-2">Tablero del enemigo</h3>
          <Board
            :board="opponentBoard"
            :is-own-board="false"
            @move="handleMove"
          />
        </div>
      </div>

      <!-- Indicador de movimientos -->
      <Moves :moves="localGame.moves" class="mt-6" />
    </div>
  </Layout>
</template>

<script>
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';
import Layout from '@/Layouts/Layout.vue';
import Board from '@/Components/Board.vue';
import Moves from '@/Components/MoveIndicator.vue';

export default {
  components: { Layout, Board, Moves },

  props: {
    game: Object,
    auth: Object,
  },

  data() {
    return {
      localGame: this.game,
      lastMoveId: 0,
      polling: null,
      isSendingMove: false,
      currentRequest: null,
      requestCanceled: false,
      opponentBoard: Array.from({ length: 8 }, () => Array(8).fill(0)),
      lastMoveResult: null,
      lastMoveX: null,
      lastMoveY: null,
      moveConfirmed: false,
      errorMessage: null,
      errorCount: 0,
      lastSuccessfulPoll: Date.now(),
      timeoutMessage: null,
      joinTimeoutMessage: null,
      lastMoveTime: null,
      shortTimeoutDuration: 30000, // 30 seconds
      longTimeoutDuration: 90000, // 90 seconds (1 minute 30 seconds)
      joinTimeoutDuration: 60000, // 1 minute
      mountTime: Date.now(), // Track when the component is mounted
    };
  },

  computed: {
    ownBoard() {
      return (
        this.localGame.boards.find(
          (b) => b.player_id === this.auth.user?.id
        ) || {
          grid: Array.from({ length: 8 }, () => Array(8).fill(0)),
        }
      );
    },
    isMyTurn() {
      const lastMove = this.localGame.moves.length
        ? this.localGame.moves[this.localGame.moves.length - 1]
        : null;
      return !lastMove
        ? this.localGame.player_1 === this.auth.user?.id
        : lastMove.player_id !== this.auth.user?.id;
    },
  },

  mounted() {
    this.updateOpponentBoard();
    if (this.localGame.status === 'active') {
      this.startPolling();
      this.updateLastMoveTime();
      if (window.location.pathname.includes('/force-turn-change')) {
        Inertia.visit(`/games/${this.localGame.id}`);
      }
    } else if (this.localGame.status === 'waiting') {
      this.checkJoinTimeout();
    }
  },

  beforeUnmount() {
    console.log('Component unmounting, cleaning up resources');
    this.stopPolling();

    if (this._pollTimeoutId) {
      clearTimeout(this._pollTimeoutId);
      this._pollTimeoutId = null;
    }

    if (this._errorTimeoutId) {
      clearTimeout(this._errorTimeoutId);
      this._errorTimeoutId = null;
    }

    if (this._moveConfirmTimeoutId) {
      clearTimeout(this._moveConfirmTimeoutId);
      this._moveConfirmTimeoutId = null;
    }

    if (this._moveErrorTimeoutId) {
      clearTimeout(this._moveErrorTimeoutId);
      this._moveErrorTimeoutId = null;
    }

    if (this._timeoutTimeoutId) {
      clearTimeout(this._timeoutTimeoutId);
      this._timeoutTimeoutId = null;
    }

    if (this._joinTimeoutId) {
      clearTimeout(this._joinTimeoutId);
      this._joinTimeoutId = null;
    }

    this.localGame = null;
    this.opponentBoard = null;
  },

  methods: {
    makeMove({ x, y }) {
      console.log('Intentando hacer movimiento', { x, y, game_status: this.localGame.status, is_sending: this.isSendingMove });
      if (this.localGame.status !== 'active' || this.isSendingMove || !this.isMyTurn) {
        console.log('Movimiento bloqueado', { status: this.localGame.status, is_sending: this.isSendingMove, is_my_turn: this.isMyTurn });
        return;
      }

      const wasPolling = this.polling;
      if (wasPolling) {
        this.stopPolling();
      }

      this.isSendingMove = true;

      if (this._moveConfirmTimeoutId) {
        clearTimeout(this._moveConfirmTimeoutId);
        this._moveConfirmTimeoutId = null;
      }

      if (this._moveErrorTimeoutId) {
        clearTimeout(this._moveErrorTimeoutId);
        this._moveErrorTimeoutId = null;
      }

      Inertia.post(
        `/games/${this.localGame.id}/move`,
        { x, y },
        {
          preserveScroll: true,
          onSuccess: (page) => {
            console.log('Movimiento exitoso', page.props);

            try {
              this.localGame = JSON.parse(JSON.stringify(page.props.game));
              this.lastMoveX = x;
              this.lastMoveY = y;
              this.updateLastMoveTime();

              this.$nextTick(() => {
                this.updateOpponentBoard();
              });

              if (page.props.flash?.message) {
                this.lastMoveResult = page.props.flash.result;
                this.moveConfirmed = true;

                this._moveConfirmTimeoutId = setTimeout(() => {
                  this._moveConfirmTimeoutId = null;
                  this.moveConfirmed = false;
                }, 5000);
              }
            } catch (error) {
              console.error('Error processing move response:', error);
              this.errorMessage = 'Error al procesar la respuesta del servidor.';

              this._moveErrorTimeoutId = setTimeout(() => {
                this._moveErrorTimeoutId = null;
                this.errorMessage = null;
              }, 5000);
            }
          },
          onError: (errors) => {
            console.error('Error al realizar el movimiento', errors);
            this.lastMoveResult = 'error';
            this.errorMessage = errors.flash?.error || 'No se pudo completar la acción.';

            this._moveErrorTimeoutId = setTimeout(() => {
              this._moveErrorTimeoutId = null;
              this.lastMoveResult = null;
              this.errorMessage = null;
            }, 5000);
          },
          onFinish: () => {
            console.log('Finalizó el intento de movimiento');
            this.isSendingMove = false;

            if (wasPolling && this.localGame.status === 'active') {
              setTimeout(() => {
                this.startPolling();
              }, 500);
            }
          },
        }
      );
    },

    startPolling() {
      console.log('Starting polling');
      if (this.polling) {
        console.log('Already polling, not starting again');
        return;
      }

      this.cancelCurrentRequest();
      this.errorCount = 0;
      this.lastSuccessfulPoll = Date.now();
      this.polling = null;
      this.poll();
    },

    cancelCurrentRequest() {
      if (this.currentRequest && typeof this.currentRequest.cancel === 'function') {
        console.log('Canceling in-flight request');
        this.requestCanceled = true;
        this.currentRequest.cancel();
        this.currentRequest = null;
      }
    },

    poll() {
      if (this.polling === false) return;

      if (this.currentRequest) {
        console.log('Request already in flight, not starting another');
        return;
      }

      this.polling = true;
      this.requestCanceled = false;

      const controller = new AbortController();
      const signal = controller.signal;

      this.currentRequest = {
        controller,
        cancel: () => controller.abort()
      };

      Inertia.get(
        `/games/${this.localGame.id}/poll`,
        {
          last_move_id: this.lastMoveId,
          _: Date.now()
        },
        {
          preserveState: true,
          preserveScroll: true,
          only: ['game', 'last_move_id'],
          headers: {
            'X-Inertia-Partial-Component': 'Game/Play'
          },
          onCancelToken: (cancelToken) => {
            this.currentRequest.cancelToken = cancelToken;
          },
          onSuccess: ({ props }) => {
            this.currentRequest = null;

            if (this.requestCanceled) {
              console.log('Request was canceled, ignoring response');
              return;
            }

            this.lastSuccessfulPoll = Date.now();
            this.errorCount = 0;

            if (props?.game) {
              try {
                const newMoveCount = props.game.moves.length;
                const currentMoveCount = this.localGame.moves.length;
                const statusChanged = props.game.status !== this.localGame.status;

                if (newMoveCount !== currentMoveCount || statusChanged) {
                  console.log('Game state changed, updating UI');
                  this.lastMoveId = props.last_move_id ?? this.lastMoveId;
                  this.localGame = JSON.parse(JSON.stringify(props.game));

                  if (newMoveCount > currentMoveCount) {
                    const lastMove = props.game.moves[props.game.moves.length - 1];
                    this.lastMoveX = lastMove.x;
                    this.lastMoveY = lastMove.y;
                    this.lastMoveResult = lastMove.result;
                    this.updateLastMoveTime();
                  }

                  this.$nextTick(() => {
                    this.updateOpponentBoard();
                  });
                }
              } catch (error) {
                console.error('Error processing game data:', error);
              }
            }

            if (this.localGame.status === 'active' && this.isMyTurn) {
              this.checkTimeout();
            }

            if (this.localGame.status === 'active') {
              const pollInterval = this.isMyTurn ? 1000 : 2000;

              if (this._pollTimeoutId) {
                clearTimeout(this._pollTimeoutId);
              }

              this._pollTimeoutId = setTimeout(() => {
                this._pollTimeoutId = null;
                this.poll();
              }, pollInterval);
            } else {
              console.log('Game no longer active, stopping polling');
              this.polling = false;
            }
          },
          onError: (errors) => {
            this.currentRequest = null;

            if (this.requestCanceled) {
              console.log('Request was canceled, ignoring error');
              return;
            }

            this.errorCount++;
            console.error(`Error en poll (${this.errorCount})`, errors);

            const backoffFactor = Math.min(1.5 + (this.errorCount * 0.1), 2.5);
            const retryDelay = Math.min(
              1000 * Math.pow(backoffFactor, this.errorCount),
              10000
            );

            console.log(`Reintentando en ${retryDelay/1000}s (intento ${this.errorCount})`);

            if (this._errorTimeoutId) {
              clearTimeout(this._errorTimeoutId);
            }

            this._errorTimeoutId = setTimeout(() => {
              this._errorTimeoutId = null;
              if (this.localGame.status === 'active') this.poll();
            }, retryDelay);
          },
          onFinish: () => {
            if (!this.requestCanceled) {
              this.currentRequest = null;
            }
          }
        }
      );
    },

    stopPolling() {
      console.log('Stopping polling');
      this.cancelCurrentRequest();
      this.polling = false;
    },

    updateOpponentBoard() {
      const fresh = Array.from({ length: 8 }, () => Array(8).fill(0));

      this.localGame.moves
        .filter((m) => m.player_id === this.auth.user?.id)
        .forEach((m) => {
          fresh[m.y][m.x] = m.result;
        });

      this.opponentBoard = fresh;
    },

    handleMove(coords) {
      if (this.isMyTurn) {
        this.makeMove(coords);
      } else {
        console.log('No es tu turno');
      }
    },

    updateLastMoveTime() {
      this.lastMoveTime = Date.now();
      if (this._timeoutTimeoutId) {
        clearTimeout(this._timeoutTimeoutId);
        this._timeoutTimeoutId = null;
      }
    },

    checkJoinTimeout() {
      if (this.localGame.status !== 'waiting') return;

      const createdAt = new Date(this.localGame.created_at);
      const createdAtTime = isNaN(createdAt.getTime()) ? this.mountTime : createdAt.getTime();
      const timeSinceCreation = Date.now() - createdAtTime;

      console.log('Debug - checkJoinTimeout:', {
        createdAt: this.localGame.created_at,
        createdAtTime,
        currentTime: Date.now(),
        timeSinceCreation,
        joinTimeoutDuration: this.joinTimeoutDuration
      });

      if (timeSinceCreation > this.joinTimeoutDuration) {
        this.joinTimeoutMessage = 'Partida cancelada por falta de jugadores.';
        this.cancelGame();
      } else if (!this._joinTimeoutId) {
        const timeLeft = this.joinTimeoutDuration - timeSinceCreation;
        this._joinTimeoutId = setTimeout(() => {
          this._joinTimeoutId = null;
          this.checkJoinTimeout();
        }, timeLeft);
      }
    },

    checkJoinTimeout() {
      if (this.localGame.status !== 'waiting') return;

      const createdAt = new Date(this.localGame.created_at).getTime();
      const timeSinceCreation = Date.now() - createdAt;

      if (timeSinceCreation > this.joinTimeoutDuration) {
        this.joinTimeoutMessage = 'Partida cancelada por falta de jugadores.';
        this.cancelGame();
      } else if (!this._joinTimeoutId) {
        const timeLeft = this.joinTimeoutDuration - timeSinceCreation;
        this._joinTimeoutId = setTimeout(() => {
          this._joinTimeoutId = null;
          this.checkJoinTimeout();
        }, timeLeft);
      }
    },

    cancelGame() {
      if (this.isSendingMove) return;

      this.isSendingMove = true;
      Inertia.post(`/games/${this.localGame.id}/force-turn-change`, { action: 'cancel' }, {
        preserveScroll: true,
        onSuccess: (page) => {
          console.log('Game cancelled', page.props);
          this.localGame = JSON.parse(JSON.stringify(page.props.game));
          this.stopPolling();
        },
        onError: (errors) => {
          console.error('Error cancelling game', errors);
          this.errorMessage = errors.flash?.error || 'No se pudo cancelar la partida.';
          this._moveErrorTimeoutId = setTimeout(() => {
            this._moveErrorTimeoutId = null;
            this.errorMessage = null;
          }, 5000);
        },
        onFinish: () => {
          this.isSendingMove = false;
        },
      });
    },

    requestTurnChange(action = 'pass') {
      Inertia.post(`/games/${this.localGame.id}/force-turn-change`, { action }, {
        preserveScroll: true,
        onSuccess: (page) => {
          console.log('Action executed', { action, pageProps: page.props });
          this.localGame = JSON.parse(JSON.stringify(page.props.game));
          this.$nextTick(() => {
            this.updateOpponentBoard();
          });
          if (page.props.game.status === 'finished') {
            this.stopPolling();
          }
        },
        onError: (errors) => {
          console.error('Error executing action', errors);
          this.errorMessage = errors.flash?.error || 'No se pudo completar la acción.';
          this._moveErrorTimeoutId = setTimeout(() => {
            this._moveErrorTimeoutId = null;
            this.errorMessage = null;
          }, 5000);
        },
      });
    },

    abandonGame() {
      if (this.localGame.status !== 'active' || this.isSendingMove) {
        console.log('Cannot abandon game', { status: this.localGame.status, is_sending: this.isSendingMove });
        return;
      }

      if (confirm('¿Estás seguro de que quieres abandonar la partida? Esto le dará la victoria al otro jugador.')) {
        this.isSendingMove = true;
        Inertia.post(`/games/${this.localGame.id}/force-turn-change`, { action: 'abandon' }, {
          preserveScroll: true,
          onSuccess: (page) => {
            console.log('Game abandoned', page.props);
            this.localGame = JSON.parse(JSON.stringify(page.props.game));
            this.$nextTick(() => {
              this.updateOpponentBoard();
            });
            this.stopPolling();
          },
          onError: (errors) => {
            console.error('Error abandoning game', errors);
            this.errorMessage = errors.flash?.error || 'No se pudo abandonar la partida.';
            this._moveErrorTimeoutId = setTimeout(() => {
              this._moveErrorTimeoutId = null;
              this.errorMessage = null;
            }, 5000);
          },
          onFinish: () => {
            this.isSendingMove = false;
          },
        });
      }
    },
  },

  watch: {
    'localGame.status'(newStatus, oldStatus) {
      console.log(`Game status changed from ${oldStatus} to ${newStatus}`);

      if (newStatus === 'active') {
        if (!this.polling) {
          console.log('Game is active, starting polling');
          this.startPolling();
        }
        if (this._joinTimeoutId) {
          clearTimeout(this._joinTimeoutId);
          this._joinTimeoutId = null;
          this.joinTimeoutMessage = null;
        }
      } else if (newStatus === 'finished') {
        console.log('Game is finished, stopping polling');
        this.stopPolling();

        this.errorMessage = null;
        this.lastMoveResult = null;
        this.moveConfirmed = false;
        this.timeoutMessage = null;
        if (this._joinTimeoutId) {
          clearTimeout(this._joinTimeoutId);
          this._joinTimeoutId = null;
          this.joinTimeoutMessage = null;
        }
      } else if (newStatus === 'waiting' && oldStatus !== 'waiting') {
        this.checkJoinTimeout();
      }
    },
  },
};
</script>