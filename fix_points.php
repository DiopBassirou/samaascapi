<?php
// Script pour recalculer les points des PouleTeams à partir des MatchGames terminés
$matches = \App\Models\MatchGame::where('statut', 'TERMINE')->get();
$standingsService = app(\App\Services\StandingsCalculationService::class);

// Réinitialiser les points des PouleTeams
\App\Models\PouleTeam::query()->update([
    'joues' => 0, 'victoires' => 0, 'nuls' => 0, 'defaites' => 0,
    'buts_pour' => 0, 'buts_contre' => 0, 'points' => 0
]);
\App\Models\Asc::query()->update([
    'matchs_joues' => 0, 'victoires' => 0, 'nuls' => 0, 'defaites' => 0,
    'buts_pour' => 0, 'buts_contre' => 0, 'points' => 0
]);

foreach ($matches as $match) {
    $standingsService->updatePouleTeamStats($match);
}

echo "Points recalculés avec succès !";
