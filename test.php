<?php
$ascCode = 'ASC-MD';
$pouleTeamIds = \App\Models\PouleTeam::where('asc_code', $ascCode)->pluck('id')->toArray();
$matches = \App\Models\MatchGame::with(['asc', 'opponent.asc', 'events'])
    ->where(function($query) use ($ascCode, $pouleTeamIds) {
        $query->where('asc_code', $ascCode)
              ->orWhere('adversaire_code', $ascCode)
              ->orWhereIn('poule_team_id', $pouleTeamIds);
    })
    ->orderBy('date_match', 'asc')
    ->get();
echo json_encode($matches);
