<template>
    <Layout>
        <div class="bg-gray-800 p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-white mb-4">
                Estadísticas de partidas
            </h2>
            <GameChart :stats="stats" @chart-click="handleChartClick" />
            <GameModal
                v-if="showModal"
                :games="filteredGames"
                :show="showModal"
                :filter="activeFilter"
                @close="closeModal"
            />
        </div>
    </Layout>
</template>

<script>
import Layout from '@/Layouts/Layout.vue';
import GameChart from '@/Components/GameChart.vue';
import GameModal from '@/Components/GameModal.vue';

export default {
    components: { Layout, GameChart, GameModal },
    props: {
        games: Array,
        auth: Object,
    },
    data() {
        return {
            showModal: false,
            filteredGames: [],
            activeFilter: 'all', // 'all', 'won', 'lost'
        };
    },
    computed: {
        stats() {
            const played = this.games.length;
            const won = this.games.filter(game => game.winner === this.auth.user.id).length;
            const lost = played - won;
            return { played, won, lost };
        },
    },
    created() {
        this.filteredGames = [...this.games];
    },
    methods: {
        handleChartClick(filter) {
            this.activeFilter = filter;
            this.filterGames(filter);
            if (this.filteredGames.length > 0) {
                this.showModal = true;
            }
        },
        filterGames(filter) {
            this.activeFilter = filter;
            const userId = String(this.auth?.user?.id || '');
            console.log(`Filtering games for user ID: ${userId}, Filter: ${filter}`);
            if (filter === 'all') {
                this.filteredGames = [...this.games];
                console.log('All games:', this.filteredGames.map(g => ({ id: g.id, winner: g.winner })));
            } else if (filter === 'won') {
                this.filteredGames = this.games.filter(game => String(game.winner) === userId);
                console.log('Won games:', this.filteredGames.map(g => ({ id: g.id, winner: g.winner })));
            } else if (filter === 'lost') {
                this.filteredGames = this.games.filter(game => String(game.winner) !== userId);
                console.log('Lost games:', this.filteredGames.map(g => ({ id: g.id, winner: g.winner })));
            }
        },
        closeModal() {
            this.showModal = false;
        },
    },
};
</script>
