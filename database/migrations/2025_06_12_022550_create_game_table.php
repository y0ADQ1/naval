<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_1')->constrained('users')->onDelete('cascade');
            $table->foreignId('player_2')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('status')->default('waiting'); 
            $table->foreignId('winner')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->dropForeign(['player_1']);
            $table->dropForeign(['player_2']);
            $table->dropForeign(['winner']);
            $table->dropColumn(['player_1', 'player_2', 'status', 'winner']);
        });
    }
};
