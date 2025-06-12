<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoveController extends Controller
{
    public function store(Request $request, Game $game)
    {
        $this->validate($request, [
            'x' => 'required|integer|min:0|max:2',
            'y' => 'required|integer|min:0|max:2',
        ]);

        if ($game->status !== 'active' || !$this->isPlayerTurn($game)) {
            return response()->json(['error' => 'Invalid game status or not your turn'], 403);
        }

        $opponent = $game->player1_id == Auth::id() ? $game->player2_id : $game->player1_id;
        $opponentBoard = Board::where('game_id', $game->id)
            ->where('user_id', $opponent)
            ->first();

        $grid = $opponentBoard->grid;
        $result = $grid[$request->x][$request->y] ? 'hit' : 'miss';

        Move::create([
            'game_id' => $game->id,
            'user_id' => Auth::id(),
            'x' => $request->x,
            'y' => $request->y,
            'result' => $result,
        ]);

        $hits = Move::where('game_id', $game->id)
            ->where('user_id', Auth::id())
            ->where('result', 'hit')
            ->count();

        if ($hits >= 15) {
            $game->update(['status' => 'finished', 'winner_id' => Auth::id()]);
        }

        return response()->json(['message' => 'Move recorded successfully', 'move' => $move], 201);
    }

    public function poll(Request $request, Game $game)
    {
        $lastMoveId = $request->query('last_move_id', 0);
        $timeout = 30;
        $start = time();

        while (time() - $start < $timeout) {
            $latestMove = Move::where('game_id', $game->id)
                ->where('id', '>', $lastMoveId)
                ->latest()
                ->first();

            if ($latestMove) {
                return response()->json([
                    'game' => $game->fresh()->load('boards', 'moves'),
                    'last_move_id' => $latestMove->id,
                ]);
            }

            sleep(1);
        }

        return response()->json(['status' => 'no_changes']);
    }

    private function isPlayerTurn(Game $game)
    {
        $lastMove = Move::where('game_id', $game->id)->latest()->first();
        if (!$lastMove) {
            return $game->player1_id == Auth::id(); 
        }
        return $lastMove->user_id != Auth::id();
    }
}
