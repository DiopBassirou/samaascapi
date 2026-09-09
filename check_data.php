<?php
$user = \App\Models\User::first();
echo "ASC: " . $user->asc_code . "\n";
echo "Players: " . \App\Models\Player::where('asc_code', $user->asc_code)->where('is_active', true)->count() . "\n";
echo "Matches: " . \App\Models\MatchGame::where('asc_code', $user->asc_code)->count() . "\n";
echo "Events: " . \App\Models\MatchEvent::count() . "\n";
echo "Convocations: " . \App\Models\Convocation::count() . "\n";

$match = \App\Models\MatchGame::where('asc_code', $user->asc_code)->where('statut', 'EN_COURS')->with(['opponent', 'events.player'])->first();
if ($match) {
    echo "\nMatch EN_COURS: Notre ASC vs " . ($match->opponent->nom_equipe ?? 'N/A') . " ({$match->score_asc}-{$match->score_adv})\n";
    echo "Events:\n";
    foreach ($match->events as $e) {
        $playerName = $e->player ? $e->player->nom : 'N/A';
        echo "  [{$e->minute}'] {$e->type} - {$playerName} - {$e->description}\n";
    }
}

$classement = \App\Models\PouleTeam::where('poule_id', \App\Models\Poule::where('asc_code', $user->asc_code)->first()->id ?? 0)->get();
echo "\nPoule Teams Stats:\n";
foreach ($classement as $t) {
    echo "  {$t->nom_equipe}: J={$t->joues} V={$t->victoires} N={$t->nuls} D={$t->defaites} BP={$t->buts_pour} BC={$t->buts_contre} Pts={$t->points}\n";
}
