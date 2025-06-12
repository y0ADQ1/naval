<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Board;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::where('status', 'waiting')->get();    
        return inertia('Games/Index', ['games' => $games,]);
    }

    public function create()
    {
        $game = Game::create([
            'player1_id' => Auth::id(),
            'status' => 'waiting',
        ]);

        $this->generateBoard($game, Auth::user());
        return redirect()->route('games.show', $game->id);
    }

    public function join(Request $request, Game $game)
    {
        if($game->status !== 'waiting')
        {
            return redirect()->back()->with('error', 'El juego ya ha comenzado o ha finalizado.');
        }

        $game->update(['player2_id' => Auth::id(), 'status' => 'active']);
        $this->generateBoard($game, Auth::user());

        return redirect()->route('games.show', $game->id);
    }

    public function show(Game $game)
    {
        $this->authorize('view', $game);
         return inertia('Games/Show', [
            'game' => $game->load('boards', 'moves'),
        ]);
    }

    private function generateBoard(Game $game, User $user)
    {
        $grid = array_fill(0, 8, array_fill(0, 8, 0));
        $positions = [];
        while (count($positions) < 15) {
            $x = rand(0, 7);
            $y = rand(0, 7);
            $key = "$x-$y";
            if (!isset($positions[$key])) {
                $positions[$key] = true;
                $grid[$x][$y] = 1; 
            }
        }

        Board::create(['game_id' => $game->id,'user_id' => $user->id,'grid' => $grid,
        ]);
    }
}
