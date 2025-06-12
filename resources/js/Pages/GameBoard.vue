<template>
    <div class="min-h-screen bg-blue-900 text-white p-6">
        <Layout>
            <h1 class="text-3xl font-bold mb-6"> Batalla Naval - Juego #{{ game.id }}</h1>
            <div v-if="game.status === 'waiting'" class="text-gray-200 mb-4">Esperando otro jugador...</div>
            <div v-else class="grid md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-xl font-semibold mb-2">Tu tablero</h2>
                    <Board :board="ownBoard" :is-own-board="true"/>
                </div>
                <div>
                    <h2 class="text-xl font-semibold mb-2">Tablero de tu oponente</h2>
                    <Board :board="opponentBoard" :is-own-board="false" @move="sendMove"/>
                </div>

            </div>
            <MoveIndicator :moves="moves"/>
        </Layout>
    </div>
</template>

<script>
import Layout from '../Layouts/Layout.vue';
import Board from './Board.vue';
import MoveIndicator from '../Components/MoveIndicator.vue';
import axios from 'axios';

export default {
    components: { Layout, Board, MoveIndicator},
    props: {
        game: Object,
        ownBoard: Array,
        opponentBoard: Array,
        moves: Array,
    },
    data(){
        return {
            lastMoveId: 0,
        };
    },
    mounted(){
        this.startPolling();
    },
    methods: {
        sendMove({ x, y}) {
            axios.post(`/games/${this.game.id}/move`, { x, y })
            .catch(error => console.error('Error al enviar movimiento:', error));
        }
        startPolling(){
            axios.get(`/games/${this.game.id}/poll`, {
                params: { last_move_id: this.lastMoveId },
            })
            .then(response => {
                if (response.data.status !== 'no_changes'){
                    this.$inertia.reload({ only: ['game', 'ownBoard', 'opponentBoard', 'moves'] });
                    this.lastMoveId = response.data.last_move_id || this.lastMoveId;
                }
                this.startPolling();
            })
            .catch(error=> {
                console.error('Error en hacer polling:', error);
                setTimeOut(() => this.startPolling(), 10000);
            });
        },
    },
};
</script>