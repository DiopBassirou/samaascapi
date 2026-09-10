<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ascs', function (Blueprint $table) {
            $table->integer('points')->default(0);
            $table->integer('matchs_joues')->default(0);
            $table->integer('victoires')->default(0);
            $table->integer('nuls')->default(0);
            $table->integer('defaites')->default(0);
            $table->integer('buts_pour')->default(0);
            $table->integer('buts_contre')->default(0);
            $table->string('poule')->nullable(); // Optionnel : à associer à une poule
        });
    }

    public function down(): void
    {
        Schema::table('ascs', function (Blueprint $table) {
            $table->dropColumn([
                'points', 'matchs_joues', 'victoires', 'nuls', 'defaites', 'buts_pour', 'buts_contre', 'poule'
            ]);
        });
    }
};
