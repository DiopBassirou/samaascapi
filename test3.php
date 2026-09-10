<?php
$matches = \App\Models\MatchGame::all(['id', 'asc_code', 'adversaire_code', 'score_asc', 'score_adv', 'statut']);
foreach($matches as $m) {
    echo $m->id . ' - ' . $m->statut . ' (' . $m->score_asc . ' - ' . $m->score_adv . ")\n";
}
