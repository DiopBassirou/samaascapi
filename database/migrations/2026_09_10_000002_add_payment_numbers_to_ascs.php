<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ascs', function (Blueprint $table) {
            // Numéros de paiement configurés par le Trésorier
            $table->string('wave_numero')->nullable()->after('cotisation_objectif');
            $table->string('om_numero')->nullable()->after('wave_numero');
            // Photo du récépissé (upload par le Président)
            $table->string('recepisse_path')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::table('ascs', function (Blueprint $table) {
            $table->dropColumn(['wave_numero', 'om_numero']);
        });
    }
};
