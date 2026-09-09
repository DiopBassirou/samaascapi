<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('asc_code');
            $table->string('nom');
            $table->string('poste');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('asc_code')->references('code_unique')->on('ascs')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('players');
    }
};
