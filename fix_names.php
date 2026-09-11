<?php
// Script pour nettoyer les noms des ASC (supprimer les accents)

$fixes = [
    'ASC-RV' => 'Reveil',
    'ASC-MC' => 'Marche Central',
    'ASC-ME' => 'Medine Extension',
    'ASC-SE' => 'Super Etoile',
];

foreach ($fixes as $code => $nom) {
    \App\Models\Asc::where('code_unique', $code)->update(['nom' => $nom]);
    echo "OK: $code => $nom\n";
}

echo "\n--- Liste complete ---\n";
\App\Models\Asc::all()->each(function($a) {
    echo $a->code_unique . ' => ' . $a->nom . "\n";
});
