<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();                                                  // PK autoincremental
            $table->foreignId('game_id')
                  ->constrained('game')                                   // ← nombre correcto
                  ->onDelete('cascade');

            $table->foreignId('player_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('x');                              // 0‑7
            $table->unsignedTinyInteger('y');                              // 0‑7
            $table->string('result', 4);                                   // 'hit' | 'miss'

            $table->timestamps();                                          // created_at / updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
