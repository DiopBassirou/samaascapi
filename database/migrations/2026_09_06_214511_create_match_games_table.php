<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();
            $table->string('asc_code');
            $table->foreignId('poule_team_id')->nullable()->constrained('poule_teams')->onDelete('set null');
            $table->dateTime('date_match');
            $table->integer('score_asc')->nullable();
            $table->integer('score_adv')->nullable();
            $table->string('statut')->default('A_VENIR'); // A_VENIR, EN_COURS, TERMINE
            $table->timestamps();
            
            $table->foreign('asc_code')->references('code_unique')->on('ascs')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('match_games');
    }
};
