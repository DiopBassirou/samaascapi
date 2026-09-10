<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Seed tous les rôles de l'application Sama ASC.
     *
     * Hiérarchie :
     *  1 - SUPPORTER       : Peut voir les matchs, cotiser, recevoir les notifs
     *  2 - TRESORIER       : Peut configurer les numéros Wave/OM, voir les cotisations
     *  3 - CHARGE_DE_COM   : Peut créer des annonces, convoquer les joueurs, mettre les scores
     *  4 - PRESIDENT       : Gère le bureau de l'ASC, valide les membres
     *  5 - SUPER_ADMIN     : Gère toutes les ASC, valide les présidents, crée les poules
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'nom' => 'SUPPORTER'],
            ['id' => 2, 'nom' => 'TRESORIER'],
            ['id' => 3, 'nom' => 'CHARGE_DE_COM'],
            ['id' => 4, 'nom' => 'PRESIDENT'],
            ['id' => 5, 'nom' => 'SUPER_ADMIN'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], ['nom' => $role['nom']]);
        }
    }
}
