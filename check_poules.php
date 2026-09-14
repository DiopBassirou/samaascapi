<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$poules = \App\Models\Poule::with('teams')->where('categorie', 'SENIOR')->get();
foreach($poules as $p) {
    echo $p->nom . ': ' . $p->teams->count() . " teams\n";
}
