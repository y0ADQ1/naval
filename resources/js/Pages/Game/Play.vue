<template>
    <Layout>
        <div class="bg-gray-800 p-6 rounded-lg shadow-md">
            <!-- Encabezado -->
            <h2 class="text-2xl font-bold text-white mb-4">
                Partida # {{ localGame.id }} ‑ Sala de: {{ localGame.player1.name }}
            </h2>

            <!-- Mensajes de estado -->
            <div v-if="localGame.status === 'waiting'" class="text-gray-200 mb-4">
                Esperando a que otro jugador se una...
            </div>

            <div
                v-else-if="localGame.status === 'finished'"
                class="text-2xl font-bold mb-4"
                :class="localGame.winner === auth.user.id ? 'text-green-500' : 'text-red-500'"
            >
                {{ localGame.winner === auth.user.id ? '¡Has ganado!' : 'Has perdido.' }}
            </div>

            <!-- Indicador de turno mejorado -->
            <div v-if="localGame.status === 'active'" class="mb-4 p-3 rounded-md flex items-center"
                :class="isMyTurn ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-200'">
                <div class="w-3 h-3 rounded-full mr-2"
                    :class="isMyTurn ? 'bg-white animate-pulse' : 'bg-gray-400'"></div>
                <span class="font-bold">
                    {{ isMyTurn ? '¡Es tu turno!' : 'Esperando el movimiento del oponente...' }}
                </span>
            </div>

            <!-- Mensaje de resultado -->
            <!-- Mensaje de resultado con mejor visualización -->
            <div v-if="lastMoveResult" class="mb-4 p-3 rounded-md" :class="lastMoveResult === 'hit' ? 'bg-green-700 text-white' : 'bg-blue-700 text-white'">
                <span class="font-bold">Último movimiento:</span> {{ lastMoveResult === 'hit' ? '¡Impacto!' : 'Agua' }}
                <span v-if="moveConfirmed" class="ml-2 text-sm bg-gray-800 text-white px-2 py-1 rounded">✓ Confirmado</span>
            </div>

            <!-- Notificación de confirmación de movimiento -->
            <div v-if="moveConfirmed" class="mb-4 p-3 bg-green-600 text-white rounded-md">
                <span class="font-bold">✓ Movimiento registrado correctamente</span> y guardado en la base de datos.
            </div>

            <!-- Mensaje de error -->
            <div v-if="errorMessage" class="mb-4 p-3 bg-red-600 text-white rounded-md">
                <span class="font-bold">Error:</span> {{ errorMessage }}
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
            currentRequest: null, // Track the current in-flight request
            requestCanceled: false, // Flag to track if a request was canceled
            opponentBoard: Array.from({ length: 8 }, () => Array(8).fill(0)),
            lastMoveResult: null, // Estado para el resultado del último movimiento
            moveConfirmed: false, // Estado para confirmar que el movimiento fue registrado
            errorMessage: null, // Mensaje de error para mostrar al usuario
            errorCount: 0, // Counter for consecutive errors
            lastSuccessfulPoll: Date.now(), // Track when the last successful poll occurred
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
        }
    },

    beforeUnmount() {
        console.log('Component unmounting, cleaning up resources');
        this.stopPolling();

        // Clear any pending timeouts
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

        // Clear any references to large objects
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

            // Temporarily pause polling while making a move to prevent race conditions
            const wasPolling = this.polling;
            if (wasPolling) {
                this.stopPolling();
            }

            this.isSendingMove = true;

            // Clear any existing move timeouts
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
                    preserveScroll: true, // Prevent page jumping
                    onSuccess: (page) => {
                        console.log('Movimiento exitoso', page.props);

                        try {
                            // Create a deep copy of the game object to avoid reference issues
                            this.localGame = JSON.parse(JSON.stringify(page.props.game));

                            // Update the board after a short delay to ensure the DOM has updated
                            this.$nextTick(() => {
                                this.updateOpponentBoard();
                            });

                            if (page.props.flash?.message) {
                                this.lastMoveResult = page.props.flash.result; // Guardar resultado
                                this.moveConfirmed = true; // Marcar el movimiento como confirmado

                                // Ocultar la confirmación después de 5 segundos
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

                        // Mostrar el error durante 5 segundos
                        this._moveErrorTimeoutId = setTimeout(() => {
                            this._moveErrorTimeoutId = null;
                            this.lastMoveResult = null;
                            this.errorMessage = null;
                        }, 5000);
                    },
                    onFinish: () => {
                        console.log('Finalizó el intento de movimiento');
                        this.isSendingMove = false;

                        // Resume polling if it was active before
                        if (wasPolling && this.localGame.status === 'active') {
                            // Wait a short delay before resuming polling to prevent race conditions
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
            // Only start polling if we're not already polling
            if (this.polling) {
                console.log('Already polling, not starting again');
                return;
            }

            // Cancel any in-flight request
            this.cancelCurrentRequest();

            // Reset error count
            this.errorCount = 0;
            this.lastSuccessfulPoll = Date.now();

            // Set polling to null initially, it will be set to true in poll()
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
            // If polling is explicitly set to false, don't continue
            if (this.polling === false) return;

            // If there's already a request in flight, don't start another one
            if (this.currentRequest) {
                console.log('Request already in flight, not starting another');
                return;
            }

            // Set polling to true to indicate we're actively polling
            this.polling = true;

            // Reset the canceled flag
            this.requestCanceled = false;

            // Create a new controller for this request
            const controller = new AbortController();
            const signal = controller.signal;

            // Store the controller so we can cancel it if needed
            this.currentRequest = {
                controller,
                cancel: () => controller.abort()
            };

            Inertia.get(
                `/games/${this.localGame.id}/poll`,
                {
                    last_move_id: this.lastMoveId,
                    _: Date.now() // Cache buster
                },
                {
                    preserveState: true,
                    preserveScroll: true, // Prevent page jumping
                    only: ['game', 'last_move_id'], // Only update these properties
                    headers: {
                        'X-Inertia-Partial-Component': 'Game/Play'
                    },
                    onCancelToken: (cancelToken) => {
                        // Store the cancel token
                        this.currentRequest.cancelToken = cancelToken;
                    },
                    onSuccess: ({ props }) => {
                        // Clear the current request
                        this.currentRequest = null;

                        // If the request was canceled, don't process the response
                        if (this.requestCanceled) {
                            console.log('Request was canceled, ignoring response');
                            return;
                        }

                        // Update last successful poll time
                        this.lastSuccessfulPoll = Date.now();

                        // Reset error count on success
                        this.errorCount = 0;

                        if (props?.game) {
                            try {
                                // Only update if there are actual changes
                                const newMoveCount = props.game.moves.length;
                                const currentMoveCount = this.localGame.moves.length;
                                const statusChanged = props.game.status !== this.localGame.status;

                                if (newMoveCount !== currentMoveCount || statusChanged) {
                                    console.log('Game state changed, updating UI');
                                    this.lastMoveId = props.last_move_id ?? this.lastMoveId;

                                    // Create a deep copy of the game object to avoid reference issues
                                    this.localGame = JSON.parse(JSON.stringify(props.game));

                                    // Update the board after a short delay to ensure the DOM has updated
                                    this.$nextTick(() => {
                                        this.updateOpponentBoard();
                                    });
                                }
                            } catch (error) {
                                console.error('Error processing game data:', error);
                                // Don't count this as a polling error
                            }
                        }

                        if (this.localGame.status === 'active') {
                            // Use dynamic polling interval: poll more frequently when it's the player's turn
                            const pollInterval = this.isMyTurn ? 1000 : 2000;

                            // Clear any existing timeout
                            if (this._pollTimeoutId) {
                                clearTimeout(this._pollTimeoutId);
                            }

                            // Store the timeout ID so it can be cleared if needed
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
                        // Clear the current request
                        this.currentRequest = null;

                        // If the request was canceled, don't process the error
                        if (this.requestCanceled) {
                            console.log('Request was canceled, ignoring error');
                            return;
                        }

                        // Increment error count
                        this.errorCount++;
                        console.error(`Error en poll (${this.errorCount})`, errors);

                        // Calculate retry delay with exponential backoff
                        const backoffFactor = Math.min(1.5 + (this.errorCount * 0.1), 2.5);
                        const retryDelay = Math.min(
                            1000 * Math.pow(backoffFactor, this.errorCount),
                            10000 // Max 10 seconds
                        );

                        console.log(`Reintentando en ${retryDelay/1000}s (intento ${this.errorCount})`);

                        // Clear any existing error timeout
                        if (this._errorTimeoutId) {
                            clearTimeout(this._errorTimeoutId);
                        }

                        // Store the error timeout ID so it can be cleared if needed
                        this._errorTimeoutId = setTimeout(() => {
                            this._errorTimeoutId = null;
                            if (this.localGame.status === 'active') this.poll();
                        }, retryDelay);
                    },
                    onFinish: () => {
                        // If the request completed normally (not canceled), clear it
                        if (!this.requestCanceled) {
                            this.currentRequest = null;
                        }
                    }
                }
            );
        },

        stopPolling() {
            console.log('Stopping polling');

            // Cancel any in-flight request
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
    },

    watch: {
        'localGame.status'(newStatus, oldStatus) {
            console.log(`Game status changed from ${oldStatus} to ${newStatus}`);

            if (newStatus === 'active') {
                // Only start polling if it's not already active
                if (!this.polling) {
                    console.log('Game is active, starting polling');
                    this.startPolling();
                }
            } else if (newStatus === 'finished') {
                console.log('Game is finished, stopping polling');
                this.stopPolling();

                // Show a notification that the game has ended
                this.errorMessage = null;
                this.lastMoveResult = null;
                this.moveConfirmed = false;
            }
        },
    },
};
</script>
