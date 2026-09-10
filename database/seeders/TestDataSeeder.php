<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Asc;
use App\Models\MatchGame;
use App\Models\Poule;
use App\Models\PouleTeam;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // SUPPRIMER LES DONNÉES EXISTANTES POUR REPARTIR PROPRE
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Role::truncate();
        PouleTeam::truncate();
        Poule::truncate();
        MatchGame::truncate();
        Asc::truncate();
        User::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        Role::firstOrCreate(['id' => 1], ['nom' => 'Supporter']);
        Role::firstOrCreate(['id' => 2], ['nom' => 'Tresorier']);
        Role::firstOrCreate(['id' => 3], ['nom' => 'Charge de Communication']);
        Role::firstOrCreate(['id' => 4], ['nom' => 'President']);
        Role::firstOrCreate(['id' => 5], ['nom' => 'Admin']);

        User::firstOrCreate(['telephone' => '770000001'], [
            'nom' => 'Super', 'prenom' => 'Admin',
            'password' => Hash::make('password'), 'role_id' => 5
        ]);

        // ================================================================
        // ZONE 2A - POULE A (SENIOR) : Top Jeunesse, Marché Central, Jokko, Deukeundo, Lerr Gui
        // ================================================================
        $tj  = Asc::create(['code_unique' => 'ASC-TJ', 'nom' => 'Top Jeunesse',   'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule A', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $mc  = Asc::create(['code_unique' => 'ASC-MC', 'nom' => 'Marché Central',  'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule A', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $jk  = Asc::create(['code_unique' => 'ASC-JK', 'nom' => 'Jokko',           'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule A', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $dk  = Asc::create(['code_unique' => 'ASC-DK', 'nom' => 'Deukeundo',       'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule A', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $lg  = Asc::create(['code_unique' => 'ASC-LG', 'nom' => 'Lerr Gui',        'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule A', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);

        // ZONE 2A - POULE B (SENIOR) : Super Étoile, Bokk Jeef, Medine, Medine Extension
        $se  = Asc::create(['code_unique' => 'ASC-SE', 'nom' => 'Super Étoile',    'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule B', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $bj  = Asc::create(['code_unique' => 'ASC-BJ', 'nom' => 'Bokk Jeef',       'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule B', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $md  = Asc::create(['code_unique' => 'ASC-MD', 'nom' => 'Medine',           'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule B', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $me  = Asc::create(['code_unique' => 'ASC-ME', 'nom' => 'Medine Extension', 'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule B', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);

        // ZONE 2A - POULE C (SENIOR) : Super Rail, Diamono, Teranga, Réveil
        $sr  = Asc::create(['code_unique' => 'ASC-SR', 'nom' => 'Super Rail',      'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule C', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $dm  = Asc::create(['code_unique' => 'ASC-DM', 'nom' => 'Diamono',         'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule C', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $tr  = Asc::create(['code_unique' => 'ASC-TR', 'nom' => 'Teranga',         'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule C', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);
        $rv  = Asc::create(['code_unique' => 'ASC-RV', 'nom' => 'Réveil',          'ville' => 'Mbour', 'zone' => 'Zone 2A', 'poule' => 'Poule C', 'statut' => 'VALIDEE', 'is_active' => true, 'points' => 0, 'matchs_joues' => 0, 'victoires' => 0, 'defaites' => 0, 'buts_pour' => 0, 'buts_contre' => 0]);

        // ================================================================
        // DÉFINITION DES POULES OFFICIELLES (SENIOR & CADET)
        // ================================================================
        $pouleA_Sr = Poule::create(['nom' => 'Poule A', 'zone' => 'Zone 2A', 'categorie' => 'SENIOR', 'edition' => 'Navétanes 2026']);
        $pouleB_Sr = Poule::create(['nom' => 'Poule B', 'zone' => 'Zone 2A', 'categorie' => 'SENIOR', 'edition' => 'Navétanes 2026']);
        $pouleC_Sr = Poule::create(['nom' => 'Poule C', 'zone' => 'Zone 2A', 'categorie' => 'SENIOR', 'edition' => 'Navétanes 2026']);
        $pouleA_Cd = Poule::create(['nom' => 'Poule A', 'zone' => 'Zone 2A', 'categorie' => 'CADET',  'edition' => 'Navétanes 2026']);

        // Équipes Senior - Poule A
        PouleTeam::create(['poule_id' => $pouleA_Sr->id, 'nom_equipe' => 'Top Jeunesse',   'asc_code' => 'ASC-TJ']);
        PouleTeam::create(['poule_id' => $pouleA_Sr->id, 'nom_equipe' => 'Marché Central',  'asc_code' => 'ASC-MC']);
        PouleTeam::create(['poule_id' => $pouleA_Sr->id, 'nom_equipe' => 'Jokko',           'asc_code' => 'ASC-JK']);
        PouleTeam::create(['poule_id' => $pouleA_Sr->id, 'nom_equipe' => 'Deukeundo',       'asc_code' => 'ASC-DK']);
        PouleTeam::create(['poule_id' => $pouleA_Sr->id, 'nom_equipe' => 'Lerr Gui',        'asc_code' => 'ASC-LG']);

        // Équipes Senior - Poule B
        PouleTeam::create(['poule_id' => $pouleB_Sr->id, 'nom_equipe' => 'Super Étoile',    'asc_code' => 'ASC-SE']);
        PouleTeam::create(['poule_id' => $pouleB_Sr->id, 'nom_equipe' => 'Bokk Jeef',       'asc_code' => 'ASC-BJ']);
        PouleTeam::create(['poule_id' => $pouleB_Sr->id, 'nom_equipe' => 'Medine',           'asc_code' => 'ASC-MD']);
        PouleTeam::create(['poule_id' => $pouleB_Sr->id, 'nom_equipe' => 'Medine Extension', 'asc_code' => 'ASC-ME']);

        // Équipes Senior - Poule C
        PouleTeam::create(['poule_id' => $pouleC_Sr->id, 'nom_equipe' => 'Super Rail',      'asc_code' => 'ASC-SR']);
        PouleTeam::create(['poule_id' => $pouleC_Sr->id, 'nom_equipe' => 'Diamono',         'asc_code' => 'ASC-DM']);
        PouleTeam::create(['poule_id' => $pouleC_Sr->id, 'nom_equipe' => 'Teranga',         'asc_code' => 'ASC-TR']);
        PouleTeam::create(['poule_id' => $pouleC_Sr->id, 'nom_equipe' => 'Réveil',          'asc_code' => 'ASC-RV']);

        // Équipes Cadet - Poule A (Book Jeef, Top Jeunesse, Réveil, Super Rail)
        PouleTeam::create(['poule_id' => $pouleA_Cd->id, 'nom_equipe' => 'Bokk Jeef',       'asc_code' => 'ASC-BJ']);
        PouleTeam::create(['poule_id' => $pouleA_Cd->id, 'nom_equipe' => 'Top Jeunesse',   'asc_code' => 'ASC-TJ']);
        PouleTeam::create(['poule_id' => $pouleA_Cd->id, 'nom_equipe' => 'Réveil',          'asc_code' => 'ASC-RV']);
        PouleTeam::create(['poule_id' => $pouleA_Cd->id, 'nom_equipe' => 'Super Rail',      'asc_code' => 'ASC-SR']);

        // ZONE 2A - CADET (Poule A séparée) : Book Jeef, Top Jeunesse, Réveil, Super Rail
        // (déjà créés : ASC-TJ, ASC-SR, ASC-RV)
        // Book Jeef cadet — utiliser le même code car club = même structure
        // On réutilise les clubs existants; pas besoin de créer de doublons.

        // ================================================================
        // MATCHS SENIOR - POULE A (Zone 2A)
        // ================================================================
        $d09Aout = Carbon::create(2026, 8, 9,  16, 0, 0);
        $d15Aout = Carbon::create(2026, 8, 15, 16, 0, 0);
        $d31Aout = Carbon::create(2026, 8, 31, 16, 0, 0);
        $hier    = Carbon::now()->subDay()->setHour(16)->setMinute(0)->setSecond(0);

        // 09 Août
        MatchGame::create(['asc_code' => $tj->code_unique, 'adversaire_code' => $lg->code_unique, 'adversaire_nom' => $lg->nom, 'date_match' => $d09Aout,              'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 0, 'score_adv' => 0, 'phase' => 'Poule A']);
        MatchGame::create(['asc_code' => $jk->code_unique, 'adversaire_code' => $dk->code_unique, 'adversaire_nom' => $dk->nom, 'date_match' => $d09Aout->copy()->addHours(2), 'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 1, 'score_adv' => 1, 'phase' => 'Poule A']);

        // 15 Août
        MatchGame::create(['asc_code' => $lg->code_unique, 'adversaire_code' => $mc->code_unique, 'adversaire_nom' => $mc->nom, 'date_match' => $d15Aout,              'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 1, 'score_adv' => 0, 'phase' => 'Poule A']);
        MatchGame::create(['asc_code' => $tj->code_unique, 'adversaire_code' => $jk->code_unique, 'adversaire_nom' => $jk->nom, 'date_match' => $d15Aout->copy()->addHours(2), 'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 0, 'score_adv' => 2, 'phase' => 'Poule A']);

        // 31 Août
        MatchGame::create(['asc_code' => $jk->code_unique, 'adversaire_code' => $lg->code_unique, 'adversaire_nom' => $lg->nom, 'date_match' => $d31Aout,              'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 2, 'score_adv' => 1, 'phase' => 'Poule A']);
        MatchGame::create(['asc_code' => $mc->code_unique, 'adversaire_code' => $dk->code_unique, 'adversaire_nom' => $dk->nom, 'date_match' => $d31Aout->copy()->addHours(2), 'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 2, 'score_adv' => 1, 'phase' => 'Poule A']);

        // Hier
        MatchGame::create(['asc_code' => $tj->code_unique, 'adversaire_code' => $dk->code_unique, 'adversaire_nom' => $dk->nom, 'date_match' => $hier,                 'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 2, 'score_adv' => 2, 'phase' => 'Poule A']);
        MatchGame::create(['asc_code' => $mc->code_unique, 'adversaire_code' => $jk->code_unique, 'adversaire_nom' => $jk->nom, 'date_match' => $hier->copy()->addHours(2), 'statut' => 'TERMINE', 'categorie' => 'SENIOR', 'score_asc' => 0, 'score_adv' => 3, 'phase' => 'Poule A']);

        // Prochains matchs : 16 Septembre
        $d16Sept = Carbon::create(2026, 9, 16, 15, 30, 0); // 15h30
        MatchGame::create(['asc_code' => $lg->code_unique, 'adversaire_code' => $mc->code_unique, 'adversaire_nom' => $mc->nom, 'date_match' => $d16Sept, 'statut' => 'A_VENIR', 'categorie' => 'SENIOR', 'score_asc' => null, 'score_adv' => null, 'phase' => 'Poule A']);
        MatchGame::create(['asc_code' => $tj->code_unique, 'adversaire_code' => $jk->code_unique, 'adversaire_nom' => $jk->nom, 'date_match' => $d16Sept->copy()->setHour(17)->setMinute(30), 'statut' => 'A_VENIR', 'categorie' => 'SENIOR', 'score_asc' => null, 'score_adv' => null, 'phase' => 'Poule A']);

        // ================================================================
        // MATCHS CADET - Zone 2A
        // Top Jeunesse vs Super Rail : 0 - 1 (le 05 septembre)
        // ================================================================
        $d05Sept = Carbon::create(2026, 9, 5, 16, 0, 0);
        MatchGame::create(['asc_code' => $tj->code_unique, 'adversaire_code' => $sr->code_unique, 'adversaire_nom' => $sr->nom, 'date_match' => $d05Sept, 'statut' => 'TERMINE', 'categorie' => 'CADET', 'score_asc' => 0, 'score_adv' => 1, 'phase' => 'Poule A']);

        // ================================================================
        // UTILISATEURS - Associés à TOP JEUNESSE (notre ASC de test)
        // ================================================================
        User::firstOrCreate(['telephone' => '770000002'], ['nom' => 'Pres', 'prenom' => 'TopJeunesse', 'password' => Hash::make('password'), 'role_id' => 4, 'asc_code' => 'ASC-TJ']);
        User::firstOrCreate(['telephone' => '770000003'], ['nom' => 'Tresorier', 'prenom' => 'TopJeunesse', 'password' => Hash::make('password'), 'role_id' => 2, 'asc_code' => 'ASC-TJ']);
        User::firstOrCreate(['telephone' => '770000004'], ['nom' => 'Supp', 'prenom' => 'TopJeunesse', 'password' => Hash::make('password'), 'role_id' => 1, 'asc_code' => 'ASC-TJ']);
        User::firstOrCreate(['telephone' => '770000005'], ['nom' => 'Com', 'prenom' => 'TopJeunesse', 'password' => Hash::make('password'), 'role_id' => 3, 'asc_code' => 'ASC-TJ']);
    }
}

