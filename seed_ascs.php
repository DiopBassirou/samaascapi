<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asc;
use App\Models\PouleTeam;

$teams = PouleTeam::all();
foreach ($teams as $team) {
    if ($team->asc_code) {
        $exists = Asc::where('code_unique', $team->asc_code)->exists();
        if (!$exists) {
            Asc::create([
                'code_unique' => $team->asc_code,
                'nom' => $team->nom_equipe,
                'ville' => 'Mbour',
                'zone' => 'Zone 2A',
                'is_active' => true,
            ]);
            echo "Créé ASC: {$team->nom_equipe} ({$team->asc_code})\n";
        }
    }
}
echo "Toutes les ASC manquantes ont été créées !";
