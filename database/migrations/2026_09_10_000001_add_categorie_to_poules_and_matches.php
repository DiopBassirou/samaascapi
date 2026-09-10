<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Ajouter la categorie (CADET / SENIOR) sur les poules
        Schema::table('poules', function (Blueprint $table) {
            $table->enum('categorie', ['CADET', 'SENIOR'])->default('SENIOR')->after('nom');
            $table->string('edition')->nullable()->after('categorie'); // ex: "Navétanes 2026"
        });

        // 2. Ajouter la categorie sur les matchs (dénormalisé pour affichage rapide)
        Schema::table('match_games', function (Blueprint $table) {
            $table->enum('categorie', ['CADET', 'SENIOR'])->default('SENIOR')->after('asc_code');
            $table->string('adversaire_nom')->nullable()->after('categorie'); // Nom de l'équipe adverse
            $table->string('adversaire_code')->nullable()->after('adversaire_nom'); // Code ASC adverse si elle est sur la plateforme
            $table->string('lieu')->nullable()->after('adversaire_code'); // Terrain du match
            $table->string('phase')->nullable()->after('lieu'); // ex: Phase de Groupes, 1/4 Finale, etc.
        });

        // 3. Ajouter le role Tresorier dans les roles si pas encore la
        // (géré dans le seeder, pas ici)
    }

    public function down(): void {
        Schema::table('poules', function (Blueprint $table) {
            $table->dropColumn(['categorie', 'edition']);
        });
        Schema::table('match_games', function (Blueprint $table) {
            $table->dropColumn(['categorie', 'adversaire_nom', 'adversaire_code', 'lieu', 'phase']);
        });
    }
};
