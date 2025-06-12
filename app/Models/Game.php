<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['player1_id', 'player2_id', 'status', 'winner_id'];

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
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
