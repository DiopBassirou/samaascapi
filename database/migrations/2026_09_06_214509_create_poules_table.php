<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('poules', function (Blueprint $table) {
            $table->id();
            $table->string('asc_code');
            $table->string('nom');
            $table->timestamps();
            
            $table->foreign('asc_code')->references('code_unique')->on('ascs')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('poules');
    }
};
