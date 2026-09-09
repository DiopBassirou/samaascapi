<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Asc;
use App\Models\Poule;
use App\Models\PouleTeam;
use App\Models\MatchGame;
use App\Models\Player;
use App\Models\PlayerNote;
use App\Models\Transaction;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run()
    {
        // 1. ASC
        $ascCode = 'TOP_JEUNESSE_001';
        $asc = Asc::updateOrCreate(
            ['code_unique' => $ascCode],
            [
                'nom' => 'Top Jeunesse',
                'zone' => 'Zone 1',
                'ville' => 'Dakar',
                'statut' => 'VALIDEE',
            ]
        );

        // 2. Roles
        $roleCom = \App\Models\Role::firstOrCreate(['nom' => 'charge_com']);
        $roleSupporter = \App\Models\Role::firstOrCreate(['nom' => 'supporter']);

        // 3. Users (Charge_com, Supporter, etc.)
        User::updateOrCreate(
            ['telephone' => '770000001'],
            [
                'nom' => 'Com',
                'prenom' => 'Chargé',
                'password' => Hash::make('password'),
                'role_id' => $roleCom->id,
                'asc_code' => $ascCode,
            ]
        );

        $supporter = User::updateOrCreate(
            ['telephone' => '770000002'],
            [
                'nom' => 'Fidele',
                'prenom' => 'Supporter',
                'password' => Hash::make('password'),
                'role_id' => $roleSupporter->id,
                'asc_code' => $ascCode,
            ]
        );

        // 3. Poule & Teams
        $poule = Poule::firstOrCreate(['nom' => 'Poule A', 'asc_code' => $ascCode]);
        $opponent = PouleTeam::firstOrCreate(['poule_id' => $poule->id, 'nom_equipe' => 'ASC Jaraaf']);

        // 4. Players
        $players = [];
        for ($i = 1; $i <= 18; $i++) {
            $players[] = Player::firstOrCreate([
                'asc_code' => $ascCode,
                'nom' => 'Joueur ' . $i,
                'poste' => 'Milieu',
                'is_active' => true,
            ]);
        }

        // 5. Match (Terminé)
        $lastMatch = MatchGame::updateOrCreate(
            ['asc_code' => $ascCode, 'poule_team_id' => $opponent->id, 'date_match' => Carbon::now()->subDays(2)->format('Y-m-d')],
            [
                'score_asc' => 3,
                'score_adv' => 1,
                'statut' => 'TERMINE',
            ]
        );

        // Match (A venir)
        $nextOpponent = PouleTeam::firstOrCreate(['poule_id' => $poule->id, 'nom_equipe' => 'Darou Salam']);
        MatchGame::updateOrCreate(
            ['asc_code' => $ascCode, 'poule_team_id' => $nextOpponent->id, 'date_match' => Carbon::now()->addDays(5)->format('Y-m-d')],
            [
                'statut' => 'A_VENIR',
            ]
        );

        // 6. Notes / Votes for Last Match
        foreach ($players as $index => $player) {
            PlayerNote::updateOrCreate([
                'match_game_id' => $lastMatch->id,
                'player_id' => $player->id,
                'vote_hash' => hash('sha256', $supporter->id . '_' . $lastMatch->id),
            ], [
                'note' => $index == 0 ? 5 : rand(2, 4), // Le premier joueur a la meilleure note (Homme du match)
            ]);
        }

        // 7. Finance Transactions
        Transaction::firstOrCreate([
            'user_id' => $supporter->id,
            'montant' => 100000,
            'type' => 'ENTREE',
            'categorie' => 'Cotisation',
            'date_transaction' => Carbon::now()->format('Y-m-d')
        ]);

        echo "Seeding complet : ASC, Joueurs, Matchs, et Votes crées !\n";
    }
}
