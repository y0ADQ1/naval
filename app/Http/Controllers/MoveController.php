<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Board;
use App\Models\Move;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class MoveController extends Controller
{
    public function store(Request $request, Game $game)
    {
        Log::info('Intento de movimiento', [
            'user_id' => Auth::id(),
            'game_id' => $game->id,
            'game_status' => $game->status,
            'is_player_turn' => $this->isPlayerTurn($game),
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'x' => 'required|integer|min:0|max:7',
            'y' => 'required|integer|min:0|max:7',
        ]);

        $existingMove = Move::where('game_id', $game->id)
                            ->where('player_id', Auth::id())
                            ->where('x', $request->x)
                            ->where('y', $request->y)
                            ->exists();

        if ($existingMove) {
            Log::warning('Movimiento duplicado', ['x' => $request->x, 'y' => $request->y]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'Ya has realizado un movimiento en esa posición.']);
        }

        if ($game->status !== 'active' || !$this->isPlayerTurn($game)) {
            Log::error('Movimiento inválido', [
                'game_status' => $game->status,
                'is_player_turn' => $this->isPlayerTurn($game),
            ]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'Estado del juego inválido o no es tu turno.']);
        }

        $opponentId = $game->player_1 == Auth::id() ? $game->player_2 : $game->player_1;

        $opponentBoard = Board::where('game_id', $game->id)
                              ->where('player_id', $opponentId)
                              ->first();

        if (!$opponentBoard) {
            Log::error('Tablero del oponente no encontrado', ['game_id' => $game->id, 'opponent_id' => $opponentId]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'Tablero del oponente no encontrado.']);
        }

        $grid = is_string($opponentBoard->grid) ? unserialize($opponentBoard->grid) : $opponentBoard->grid;
        Log::debug('Grid del oponente', ['grid' => $grid]);

        if (!isset($grid[$request->y][$request->x])) {
            Log::error('Índice de grid inválido', ['y' => $request->y, 'x' => $request->x]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'Posición inválida en el tablero.']);
        }

        $result = $grid[$request->y][$request->x] ? 'hit' : 'miss';

        $move = Move::create([
            'game_id' => $game->id,
            'player_id' => Auth::id(),
            'x' => $request->x,
            'y' => $request->y,
            'result' => $result,
        ]);

        Log::info('Movimiento registrado', [
            'move_id' => $move->id,
            'result' => $result,
            'x' => $request->x,
            'y' => $request->y,
        ]);

        $hits = Move::where('game_id', $game->id)
                    ->where('player_id', Auth::id())
                    ->where('result', 'hit')
                    ->count();

        if ($hits >= 15) {
            $game->update(['status' => 'finished', 'winner' => Auth::id()]);
            Log::info('Juego finalizado', ['winner' => Auth::id()]);
        }

        return Inertia::render('Game/Play', [
            'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
            'auth' => ['user' => auth()->user()],
        ])->with('flash', ['message' => 'Movimiento registrado correctamente.', 'result' => $result]);
    }

    public function poll(Request $request, Game $game)
    {
        $lastMoveId = (int) $request->query('last_move_id', 0);

        $latestMove = Move::where('game_id', $game->id)
                          ->where('id', '>', $lastMoveId)
                          ->latest('id')
                          ->first();

        if ($latestMove) {
            Log::info('Nuevo movimiento detectado en polling', ['move_id' => $latestMove->id]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'last_move_id' => $latestMove->id,
                'auth' => ['user' => auth()->user()],
            ]);
        }

        return Inertia::render('Game/Play', [
            'game' => $game->load('boards', 'moves', 'player1', 'player2'),
            'last_move_id' => $lastMoveId,
            'auth' => ['user' => auth()->user()],
        ])->with('flash', ['status' => 'no_changes']);
    }

    private function isPlayerTurn(Game $game): bool
    {
        $lastMove = Move::where('game_id', $game->id)->latest('id')->first();

        Log::info('Verificando turno', [
            'game_id' => $game->id,
            'user_id' => Auth::id(),
            'last_move_player_id' => $lastMove ? $lastMove->player_id : null,
            'player_1' => $game->player_1,
            'player_2' => $game->player_2,
            'is_first_move' => !$lastMove,
            'is_player_turn' => !$lastMove ? $game->player_1 == Auth::id() : $lastMove->player_id != Auth::id(),
        ]);

        return !$lastMove
            ? $game->player_1 == Auth::id()
            : $lastMove->player_id != Auth::id();
    }
}
