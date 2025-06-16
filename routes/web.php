<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\MoveController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Remove or comment out the dashboard route
// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::post('/games', [GameController::class, 'create'])->name('games.create');
    Route::post('/games/{game}/join', [GameController::class, 'join'])->name('games.join');
    Route::get('/games/{game}/play', [GameController::class, 'show'])->name('games.play');
    Route::get('/games/stats', [GameController::class, 'stats'])->name('games.stats');
    Route::post('/games/{game}/move', [MoveController::class, 'store'])->name('moves.store');
    Route::get('/games/{game}/poll', [MoveController::class, 'poll'])->name('moves.poll');
});

require __DIR__.'/auth.php';