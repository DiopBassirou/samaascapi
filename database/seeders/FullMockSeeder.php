<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Asc;

class FullMockSeeder extends Seeder
{
    public function run()
    {
        $asc = Asc::first();
        if (!$asc) {
            $asc = Asc::create([
                'code_unique' => 'TOP_JEUNESSE_001',
                'nom' => 'Top Jeunesse',
                'ville' => 'Dakar',
                'zone' => 'Zone 4',
                'statut' => 'VALIDEE',
                'is_active' => true,
            ]);
            echo "ASC 'Top Jeunesse' créée.\n";
        }
        $ascCode = $asc->code_unique;
        $userId = User::where('asc_code', $ascCode)->first()->id ?? 1;

        // --- 1. PLAYERS (25) ---
        $players = [
            // Gardiens (3)
            ['nom' => 'Moussa Diallo', 'poste' => 'Gardien'],
            ['nom' => 'Daouda Faye', 'poste' => 'Gardien'],
            ['nom' => 'Pape Ndiaye', 'poste' => 'Gardien'],
            // Défenseurs (8)
            ['nom' => 'Ibrahima Sow', 'poste' => 'Défenseur'],
            ['nom' => 'Issa Mbaye', 'poste' => 'Défenseur'],
            ['nom' => 'Moustapha Seck', 'poste' => 'Défenseur'],
            ['nom' => 'Pape Diouf', 'poste' => 'Défenseur'],
            ['nom' => 'Modou Ndiaye', 'poste' => 'Défenseur'],
            ['nom' => 'Abdourahmane Fall', 'poste' => 'Défenseur'],
            ['nom' => 'Malick Diagne', 'poste' => 'Défenseur'],
            ['nom' => 'Cheikh Fall', 'poste' => 'Défenseur'],
            // Milieux (8)
            ['nom' => 'Lamine Diop', 'poste' => 'Milieu'],
            ['nom' => 'Serigne Diaw', 'poste' => 'Milieu'],
            ['nom' => 'Oumar Sarr', 'poste' => 'Milieu'],
            ['nom' => 'Boubacar Sané', 'poste' => 'Milieu'],
            ['nom' => 'Amadou Kane', 'poste' => 'Milieu'],
            ['nom' => 'Ousmane Ndoye', 'poste' => 'Milieu'],
            ['nom' => 'Mansour Gueye', 'poste' => 'Milieu'],
            ['nom' => 'Fallou Niang', 'poste' => 'Milieu'],
            // Attaquants (6)
            ['nom' => 'Alioune Badara', 'poste' => 'Attaquant'],
            ['nom' => 'Bassirou Touré', 'poste' => 'Attaquant'],
            ['nom' => 'Saliou Cissé', 'poste' => 'Attaquant'],
            ['nom' => 'Mamadou Thiam', 'poste' => 'Attaquant'],
            ['nom' => 'Cheikh Ndiaye', 'poste' => 'Attaquant'],
            ['nom' => 'Pape Sarr', 'poste' => 'Attaquant'],
        ];

        DB::table('players')->where('asc_code', $ascCode)->delete();
        $playerIds = [];
        foreach ($players as $p) {
            $playerIds[] = DB::table('players')->insertGetId([
                'asc_code' => $ascCode,
                'nom' => $p['nom'],
                'poste' => $p['poste'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // S'assurer que tous les rôles existent
        $roleNames = ['SUPERADMIN', 'PRESIDENT', 'TRESORIER', 'COACH', 'CHARGE_COM', 'SUPPORTER'];
        foreach ($roleNames as $rName) {
            DB::table('roles')->updateOrInsert(
                ['nom' => $rName],
                ['nom' => $rName, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Création des 5 comptes pour tester
        $rolesMapping = [
            'SUPERADMIN' => DB::table('roles')->where('nom', 'SUPERADMIN')->first()->id,
            'PRESIDENT' => DB::table('roles')->where('nom', 'PRESIDENT')->first()->id,
            'TRESORIER' => DB::table('roles')->where('nom', 'TRESORIER')->first()->id,
            'COACH' => DB::table('roles')->where('nom', 'COACH')->first()->id,
            'CHARGE_COM' => DB::table('roles')->where('nom', 'CHARGE_COM')->first()->id,
            'SUPPORTER' => DB::table('roles')->where('nom', 'SUPPORTER')->first()->id,
        ];
        $usersToCreate = [
            ['prenom' => 'Lamine', 'nom' => 'Coach', 'telephone' => '770000001', 'email' => 'coach@asc.sn', 'role' => 'COACH'],
            ['prenom' => 'Bassirou', 'nom' => 'Président', 'telephone' => '770000002', 'email' => 'president@asc.sn', 'role' => 'PRESIDENT'],
            ['prenom' => 'Awa', 'nom' => 'Trésorière', 'telephone' => '770000003', 'email' => 'tresorier@asc.sn', 'role' => 'TRESORIER'],
            ['prenom' => 'Moussa', 'nom' => 'Com', 'telephone' => '770000004', 'email' => 'com@asc.sn', 'role' => 'CHARGE_COM'],
            ['prenom' => 'Fatou', 'nom' => 'Supporter', 'telephone' => '770000005', 'email' => 'supporter@asc.sn', 'role' => 'SUPPORTER'],
        ];

        foreach ($usersToCreate as $u) {
            DB::table('users')->insert([
                'asc_code' => $ascCode,
                'role_id' => $rolesMapping[$u['role']],
                'prenom' => $u['prenom'],
                'nom' => $u['nom'],
                'telephone' => $u['telephone'],
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- 2. POULE & ADVERSAIRES ---
        $pouleId = DB::table('poules')->insertGetId([
            'asc_code' => $ascCode,
            'nom' => 'Poule A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teamJaraafId = DB::table('poule_teams')->insertGetId([
            'poule_id' => $pouleId,
            'nom_equipe' => 'ASC Jaraaf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teamNiaryId = DB::table('poule_teams')->insertGetId([
            'poule_id' => $pouleId,
            'nom_equipe' => 'Niary Tally',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teamYoffId = DB::table('poule_teams')->insertGetId([
            'poule_id' => $pouleId,
            'nom_equipe' => 'ASC Yoff',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // --- 3. MATCHS & CONVOCATIONS ---
        DB::table('match_games')->where('asc_code', $ascCode)->delete();
        
        // Match 1: EN_COURS contre ASC Jaraaf
        $matchLiveId = DB::table('match_games')->insertGetId([
            'asc_code' => $ascCode,
            'poule_team_id' => $teamJaraafId,
            'date_match' => now(),
            'score_asc' => 2,
            'score_adv' => 1,
            'statut' => 'EN_COURS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Match 2: TERMINE contre Niary Tally (Nul 0-0)
        DB::table('match_games')->insert([
            'asc_code' => $ascCode,
            'poule_team_id' => $teamNiaryId,
            'date_match' => now()->subDays(2),
            'score_asc' => 0,
            'score_adv' => 0,
            'statut' => 'TERMINE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Match 3: TERMINE contre ASC Yoff
        DB::table('match_games')->insert([
            'asc_code' => $ascCode,
            'poule_team_id' => $teamYoffId,
            'date_match' => now()->subDays(5),
            'score_asc' => 3,
            'score_adv' => 1,
            'statut' => 'TERMINE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Convoquer: 11 Titulaires, 7 Remplaçants
        $titulaires = array_slice($playerIds, 0, 11);
        $remplacants = array_slice($playerIds, 11, 7);

        DB::table('convocations')->delete();
        foreach ($titulaires as $pid) {
            DB::table('convocations')->insert([
                'match_game_id' => $matchLiveId,
                'player_id' => $pid,
                'statut' => 'TITULAIRE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach ($remplacants as $pid) {
            DB::table('convocations')->insert([
                'match_game_id' => $matchLiveId,
                'player_id' => $pid,
                'statut' => 'REMPLACANT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- 4. MATCH EVENTS (Goals with players and minutes) ---
        DB::table('match_events')->where('match_game_id', $matchLiveId)->delete();
        
        // Events pour le match EN_COURS (2-1 vs Jaraaf)
        DB::table('match_events')->insert([
            'match_game_id' => $matchLiveId,
            'player_id' => $playerIds[19], // Alioune Badara (Attaquant)
            'type' => 'BUT_ASC',
            'minute' => 23,
            'description' => 'But de Alioune Badara ! Frappe du pied droit.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('match_events')->insert([
            'match_game_id' => $matchLiveId,
            'player_id' => null,
            'type' => 'MI_TEMPS',
            'minute' => 45,
            'description' => 'Sifflet de la mi-temps. Score : 1-0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('match_events')->insert([
            'match_game_id' => $matchLiveId,
            'player_id' => null,
            'type' => 'BUT_ADV',
            'minute' => 55,
            'description' => 'Égalisation de ASC Jaraaf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('match_events')->insert([
            'match_game_id' => $matchLiveId,
            'player_id' => $playerIds[20], // Bassirou Touré (Attaquant)
            'type' => 'BUT_ASC',
            'minute' => 72,
            'description' => 'But de Bassirou Touré ! Tête sur corner.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // --- 5. MISE A JOUR STATS POULE_TEAMS (pour le match TERMINE) ---
        // Match terminé: Notre ASC 3-1 ASC Yoff => Yoff a perdu
        DB::table('poule_teams')->where('id', $teamYoffId)->update([
            'joues' => 1,
            'victoires' => 0,
            'nuls' => 0,
            'defaites' => 1,
            'buts_pour' => 1,
            'buts_contre' => 3,
            'points' => 0,
        ]);

        // Jaraaf et Niary Tally restent à 0 (match pas encore terminé)
        DB::table('poule_teams')->where('id', $teamJaraafId)->update([
            'joues' => 0, 'victoires' => 0, 'nuls' => 0, 'defaites' => 0,
            'buts_pour' => 0, 'buts_contre' => 0, 'points' => 0,
        ]);
        DB::table('poule_teams')->where('id', $teamNiaryId)->update([
            'joues' => 0, 'victoires' => 0, 'nuls' => 0, 'defaites' => 0,
            'buts_pour' => 0, 'buts_contre' => 0, 'points' => 0,
        ]);

        // --- 6. FINANCES ---
        DB::table('transactions')->where('user_id', $userId)->delete();
        $transactions = [
            ['type' => 'ENTREE', 'montant' => 500, 'categorie' => 'Cotisation', 'date_transaction' => now()],
            ['type' => 'ENTREE', 'montant' => 1000, 'categorie' => 'Don Anonyme', 'date_transaction' => now()->subHours(2)],
            ['type' => 'DEPENSE', 'montant' => 5000, 'categorie' => 'Stade / Terrain', 'date_transaction' => now()->subDay()],
            ['type' => 'DEPENSE', 'montant' => 1500, 'categorie' => 'Eau & Glace', 'date_transaction' => now()->subDay()],
            ['type' => 'DEPENSE', 'montant' => 2000, 'categorie' => 'Transport', 'date_transaction' => now()->subDay()],
        ];

        foreach ($transactions as $t) {
            DB::table('transactions')->insert(array_merge($t, [
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        
        echo "25 Players, Poule A, Adversaires (Jaraaf, Niary Tally, Yoff), Matchs (EN_COURS, A_VENIR, TERMINE), Match Events, Convocations, Stats & Finances seeded successfully for ASC: " . $ascCode . "!\n";
    }
}
