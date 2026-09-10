<?php
$ms = \App\Models\MatchGame::with(['asc', 'opponent.asc'])->where('statut', 'TERMINE')->get();
foreach($ms as $m) {
    $t1 = $m->asc ? $m->asc->nom : $m->asc_code;
    $t2 = '';
    if ($m->opponent) {
        $t2 = $m->opponent->asc ? $m->opponent->asc->nom : $m->opponent->nom_equipe;
    } else {
        $t2 = $m->adversaire_code ? $m->adversaire_code : $m->adversaire_nom;
    }
    echo $m->id . ' [' . $m->categorie . '] ' . $t1 . ' (' . $m->score_asc . ') vs ' . $t2 . ' (' . $m->score_adv . ")\n";
}
