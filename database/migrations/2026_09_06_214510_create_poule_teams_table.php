<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('poule_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poule_id')->constrained('poules')->onDelete('cascade');
            $table->string('nom_equipe');
            $table->integer('joues')->default(0);
            $table->integer('victoires')->default(0);
            $table->integer('nuls')->default(0);
            $table->integer('defaites')->default(0);
            $table->integer('buts_pour')->default(0);
            $table->integer('buts_contre')->default(0);
            $table->integer('points')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('poule_teams');
    }
};
