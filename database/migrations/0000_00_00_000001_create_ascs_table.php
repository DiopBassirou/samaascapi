<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ascs', function (Blueprint $table) {
            $table->string('code_unique')->primary();
            $table->string('nom');
            $table->string('ville');
            $table->string('zone');
            $table->enum('statut', ['EN_ATTENTE', 'VALIDEE', 'REJETEE'])->default('EN_ATTENTE');
            $table->unsignedBigInteger('president_id')->nullable(); // Le futur président
            $table->string('recepisse_path')->nullable(); // Le document scanné
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ascs');
    }
};
