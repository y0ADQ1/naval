<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public $timestamps = false;
    protected $table = 'game';
    protected $fillable = ['player_1', 'player_2', 'status', 'winner_id'];

    public function player1()
    {
        return $this->belongsTo(User::class, 'player_1');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player_2');
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
