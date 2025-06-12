<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class GameController extends Controller
{
    public function index()
    {
        $game = Game::where('status', 'waiting')->get();   
        return response()->json($game); 
        return inertia('Game/Index', ['game' => $game,]);
    }

    public function create(Request $request)
    {
        $game = Game::create([
            'player_1' => Auth::id(),
            'status' => 'waiting',
        ]);

        $this->generateBoard($game, Auth::user());

         return response()->json([
        'game' => $game,
        'message' => 'Game created successfully',
    ]);
        //return redirect()->route('games.show', $game->id);
    }

    public function join(Request $request, Game $game)
    {
        if($game->status !== 'waiting')
        {
            return redirect()->back()->with('error', 'El juego ya ha comenzado o ha finalizado.');
        }

        $game->update(['player_2' => Auth::id(), 'status' => 'active']);
        $this->generateBoard($game, Auth::user());

        return redirect()->route('games.join', $game->id);
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

        Board::create(['game_id' => $game->id,'player_id' => $user->id,'grid' => $grid,
        ]);
    }
}
