<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asc;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class InitTopJeunesseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Créer l'ASC Top Jeunesse
        $asc = Asc::firstOrCreate(
            ['code_unique' => 'TOP26'],
            [
                'nom' => 'Top Jeunesse',
                'zone' => 'Zone de Test',
                'ville' => 'Dakar',
                'is_active' => true
            ]
        );

        // 2. S'assurer que le rôle existe
        $role = Role::firstOrCreate(
            ['nom' => 'CHARGE_DE_COM'],
            [] // Pas de champ description
        );

        // 3. Créer le compte Chargé de Com
        User::updateOrCreate(
            ['telephone' => '762759658'],
            [
                'nom' => 'Chargé',
                'prenom' => 'Com',
                'password' => Hash::make('password'),
                'asc_code' => $asc->code_unique,
                'role_id' => $role->id
            ]
        );
    }
}
