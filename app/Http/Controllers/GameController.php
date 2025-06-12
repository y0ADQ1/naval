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
}
    