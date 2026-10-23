<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ascs', function (Blueprint $table) {
            $table->string('ville')->default('Mbour')->nullable()->change();
        });

        Schema::table('match_games', function (Blueprint $table) {
            $table->string('type_coupe')->nullable()->default('Coupe Zonale')->after('phase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropColumn('type_coupe');
        });

        Schema::table('ascs', function (Blueprint $table) {
            $table->string('ville')->nullable(false)->change();
        });
    }
};
