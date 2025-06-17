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

    
   public function forceTurnChange(Request $request, Game $game)
    {
        Log::info('Forcing turn change or suspension', [
            'user_id' => Auth::id(),
            'game_id' => $game->id,
            'game_status' => $game->status,
            'action' => $request->input('action', 'pass'),
        ]);

        if ($game->status !== 'active' && $game->status !== 'waiting') {
            Log::error('Cannot force turn change/suspension/cancellation: game not active or waiting', ['game_id' => $game->id]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'El juego no está activo o en espera.']);
        }

        $lastMove = Move::where('game_id', $game->id)->latest('id')->first();
        $currentPlayerId = Auth::id();
        $isPlayerTurn = !$lastMove ? $game->player_1 == $currentPlayerId : $lastMove->player_id != $currentPlayerId;

        if (!$isPlayerTurn && $request->input('action') !== 'abandon' && $request->input('action') !== 'cancel') {
            Log::warning('Turn change/suspension request from non-current player', ['user_id' => $currentPlayerId]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'No es tu turno para forzar el cambio.']);
        }

        $shortTimeoutDuration = 30; // 30 seconds
        $longTimeoutDuration = 90; // 90 seconds
        $lastMoveTime = $lastMove ? strtotime($lastMove->created_at) : strtotime($game->created_at);
        $timeSinceLastMove = time() - $lastMoveTime;

        $action = $request->input('action', 'pass');

        if ($action === 'pass') {
            if ($timeSinceLastMove < $shortTimeoutDuration) {
                Log::info('Turn change not enforced: 30-second timeout not exceeded', ['time_since_last_move' => $timeSinceLastMove]);
                return Inertia::render('Game/Play', [
                    'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                    'auth' => ['user' => auth()->user()],
                ])->with('flash', ['error' => 'El tiempo de inactividad de 30 segundos no ha sido superado.']);
            }

            // Force a dummy move to pass the turn
            $opponentId = $game->player_1 == $currentPlayerId ? $game->player_2 : $game->player_1;
            $opponentBoard = Board::where('game_id', $game->id)->where('player_id', $opponentId)->first();

            if ($opponentBoard) {
                $grid = is_string($opponentBoard->grid) ? unserialize($opponentBoard->grid) : $opponentBoard->grid;
                $x = rand(0, 7);
                $y = rand(0, 7);
                $maxAttempts = 50;
                $attempts = 0;
                while (isset($grid[$y][$x]) && $grid[$y][$x] !== 0 && $attempts < $maxAttempts) {
                    $x = rand(0, 7);
                    $y = rand(0, 7);
                    $attempts++;
                }
                if ($attempts >= $maxAttempts) {
                    Log::warning('No unused position found for dummy move');
                    return Inertia::render('Game/Play', [
                        'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                        'auth' => ['user' => auth()->user()],
                    ])->with('flash', ['error' => 'No se pudo forzar el cambio de turno.']);
                }
                $result = $grid[$y][$x] ? 'hit' : 'miss';

                Move::create([
                    'game_id' => $game->id,
                    'player_id' => $currentPlayerId,
                    'x' => $x,
                    'y' => $y,
                    'result' => $result,
                ]);

                Log::info('Dummy move created to force turn change', ['x' => $x, 'y' => $y, 'result' => $result]);
            } else {
                Log::warning('Opponent board not found, turn change not fully enforced');
            }

            Log::info('Turn change enforced due to 30-second timeout', ['game_id' => $game->id]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['message' => 'Tu turno ha sido pasado por inactividad.']);
        } elseif ($action === 'suspend') {
            if ($timeSinceLastMove < $longTimeoutDuration) {
                Log::info('Game suspension not enforced: 90-second timeout not exceeded', ['time_since_last_move' => $timeSinceLastMove]);
                return Inertia::render('Game/Play', [
                    'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                    'auth' => ['user' => auth()->user()],
                ])->with('flash', ['error' => 'El tiempo de inactividad de 90 segundos no ha sido superado.']);
            }

            // Suspend game and award victory to the opponent
            $opponentId = $game->player_1 == $currentPlayerId ? $game->player_2 : $game->player_1;
            $game->update([
                'status' => 'finished',
                'winner' => $opponentId,
                'status_reason' => 'suspended_inactivity',
            ]);

            Log::info('Game suspended due to 90-second inactivity, victory awarded', [
                'game_id' => $game->id,
                'winner' => $opponentId,
            ]);

            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['message' => 'Partida suspendida por inactividad. Victoria otorgada al oponente.']);
        } elseif ($action === 'abandon') {
            // Abandon game and award victory to the opponent immediately
            $opponentId = $game->player_1 == $currentPlayerId ? $game->player_2 : $game->player_1;
            $game->update([
                'status' => 'finished',
                'winner' => $opponentId,
                'status_reason' => 'abandoned',
            ]);

            Log::info('Game abandoned, victory awarded to opponent', [
                'game_id' => $game->id,
                'winner' => $opponentId,
                'abandoner' => $currentPlayerId,
            ]);

            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['message' => 'Has abandonado la partida. Victoria otorgada al oponente.']);
        } elseif ($action === 'cancel') {
            if ($game->status === 'waiting' && !$game->player_2) {
                $game->update([
                    'status' => 'finished',
                    'status_reason' => 'no_join',
                ]);

                Log::info('Game cancelled due to no join within 1 minute', ['game_id' => $game->id]);
                return Inertia::render('Game/Play', [
                    'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                    'auth' => ['user' => auth()->user()],
                ])->with('flash', ['message' => 'Partida cancelada por falta de jugadores.']);
            }
            Log::warning('Cancellation not applicable', ['game_id' => $game->id, 'status' => $game->status, 'player_2' => $game->player_2]);
            return Inertia::render('Game/Play', [
                'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
                'auth' => ['user' => auth()->user()],
            ])->with('flash', ['error' => 'No se puede cancelar la partida en este estado.']);
        }

        return Inertia::render('Game/Play', [
            'game' => $game->fresh()->load('boards', 'moves', 'player1', 'player2'),
            'auth' => ['user' => auth()->user()],
        ])->with('flash', ['error' => 'Acción no válida.']);
    }
}
