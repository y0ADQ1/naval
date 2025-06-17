<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::where('status', 'waiting')->with('player1')->get();
        return Inertia::render('Game/Options', [
            'games' => $games,
            'auth' => ['user' => Auth::user()],
        ]);
    }

    public function create(Request $request)
    {
        $game = Game::create([
            'player_1' => Auth::id(),
            'status' => 'waiting',
        ]);

        $this->generateBoard($game, Auth::user());

        return redirect()->route('games.play', $game);
    }

    public function join(Request $request, Game $game)
    {
        if ($game->status !== 'waiting') {
            return back()->with('error', 'El juego ya ha comenzado o ha finalizado.');
        }

        $game->update([
            'player_2' => Auth::id(),
            'status' => 'active',
        ]);

        $this->generateBoard($game, Auth::user());

        return redirect()->route('games.play', $game);
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

        Board::create([
            'game_id' => $game->id,
            'player_id' => $user->id,
            'grid' => $grid,
        ]);
    }

    public function show(Game $game)
    {
        return Inertia::render('Game/Play', [
            'game' => $game->load('boards', 'moves', 'player1', 'player2'),
            'auth' => ['user' => auth()->user()],
        ]);
    }

    public function stats()
    {
        $games = Game::where('status', 'finished')
            ->where(function ($query) {
                $query->where('player_1', auth()->id())
                    ->orWhere('player_2', auth()->id());
            })
            ->with(['moves.player', 'player1', 'player2', 'boards'])
            ->get();

        return Inertia::render('Game/Stats', [
            'games' => $games,
            'auth' => ['user' => auth()->user()],
        ]);
    }
}
