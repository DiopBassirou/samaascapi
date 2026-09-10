<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(2); // Supporter
if (!$user) {
    echo "No supporter found.\n";
    exit;
}
$ascCode = $user->asc_code;

$pouleTeamIds = \App\Models\PouleTeam::where('asc_code', $ascCode)->pluck('id')->toArray();
$matches = \App\Models\MatchGame::with(['asc', 'opponent.poule', 'events'])
    ->where('asc_code', $ascCode)
    ->orWhereIn('poule_team_id', $pouleTeamIds)
    ->orderBy('date_match', 'asc')
    ->get();

$formattedMatches = $matches->map(function ($match) use ($ascCode) {
    $isTeamB = $match->asc_code !== $ascCode;
    if ($isTeamB) {
        $match->score_asc_real = $match->score_asc;
        $match->score_adv_real = $match->score_adv;
        $match->score_asc = $match->score_adv_real;
        $match->score_adv = $match->score_asc_real;
        $match->adversaire_nom = $match->asc ? $match->asc->nom : 'Adversaire';
    } else {
        $match->adversaire_nom = $match->opponent ? $match->opponent->nom_equipe : 'Adversaire';
    }
    return $match;
});

echo "ASC Code: $ascCode\n";
echo "Matches:\n";
file_put_contents('matches.json', json_encode($formattedMatches));
