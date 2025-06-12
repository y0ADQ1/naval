<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    public $timestamps = false;
protected $fillable = ['game_id', 'player_id', 'grid'];

    protected $casts = ['grid' => 'array',];

    public function game()
    {
        return $this->belongsTo(Game::class);
        }

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
