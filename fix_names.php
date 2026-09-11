<?php
// Script pour nettoyer TOUS les accents casses dans la base

// Table des corrections
$map = [
    'Ã©' => 'e',
    'Ã¨' => 'e', 
    'Ã‰' => 'E',
    'Ã ' => 'a',
    'Ã¢' => 'a',
    'Ãª' => 'e',
    'Ã®' => 'i',
    'Ã´' => 'o',
    'Ã»' => 'u',
    'Ã§' => 'c',
    'Ã‰' => 'E',
    'Ã%' => 'E',
    'é' => 'e',
    'è' => 'e',
    'ê' => 'e',
    'à' => 'a',
    'â' => 'a',
    'î' => 'i',
    'ô' => 'o',
    'û' => 'u',
    'ç' => 'c',
    'É' => 'E',
];

echo "=== 1. Correction table ASC ===\n";
\App\Models\Asc::all()->each(function($a) use ($map) {
    $newNom = str_replace(array_keys($map), array_values($map), $a->nom);
    if ($newNom !== $a->nom) {
        $a->update(['nom' => $newNom]);
        echo "CORRIGE: {$a->code_unique} => {$newNom}\n";
    } else {
        echo "OK: {$a->code_unique} => {$a->nom}\n";
    }
});

echo "\n=== 2. Correction table poule_teams ===\n";
\App\Models\PouleTeam::all()->each(function($pt) use ($map) {
    $newNom = str_replace(array_keys($map), array_values($map), $pt->nom_equipe ?? '');
    if ($newNom !== ($pt->nom_equipe ?? '')) {
        $pt->update(['nom_equipe' => $newNom]);
        echo "CORRIGE: #{$pt->id} => {$newNom}\n";
    } else {
        echo "OK: #{$pt->id} => {$pt->nom_equipe}\n";
    }
});

echo "\n=== 3. Correction lieux des matchs ===\n";
\App\Models\MatchGame::whereNotNull('lieu')->get()->each(function($m) use ($map) {
    $newLieu = str_replace(array_keys($map), array_values($map), $m->lieu ?? '');
    if ($newLieu !== ($m->lieu ?? '')) {
        $m->update(['lieu' => $newLieu]);
        echo "CORRIGE: Match #{$m->id} lieu => {$newLieu}\n";
    }
});

echo "\n=== TERMINE ! ===\n";
