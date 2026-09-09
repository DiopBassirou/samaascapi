<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('player_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_game_id')->constrained('match_games')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->string('vote_hash'); // Anonymat
            $table->integer('note'); // 1 à 5
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('player_notes');
    }
};
