<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Poule;

$pouleB = Poule::create(['nom' => 'Poule B', 'zone' => 'Zone 2A', 'categorie' => 'CADET', 'edition' => 'Navétanes 2026']);
$teamsB = ['Super Étoile' => 'ASC-SE', 'Médine Extension' => 'ASC-ME', 'Marché Central' => 'ASC-MC', 'Deukeundo' => 'ASC-DK', 'Jokko' => 'ASC-JK'];
foreach($teamsB as $nom => $code) {
    $pouleB->teams()->create(['nom_equipe' => $nom, 'asc_code' => $code]);
}

$pouleC = Poule::create(['nom' => 'Poule C', 'zone' => 'Zone 2A', 'categorie' => 'CADET', 'edition' => 'Navétanes 2026']);
$teamsC = ['Jamono' => 'ASC-JM', 'Teranga' => 'ASC-TR', 'Leer Gui' => 'ASC-LG', 'Médine' => 'ASC-MD'];
foreach($teamsC as $nom => $code) {
    $pouleC->teams()->create(['nom_equipe' => $nom, 'asc_code' => $code]);
}

echo 'Poules Cadet B et C creees !';
