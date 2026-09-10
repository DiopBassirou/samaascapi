<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    protected $fillable = [
        'asc_code', 'poule_team_id', 'date_match',
        'score_asc', 'score_adv', 'statut', 'started_at', 'second_half_started_at',
        'categorie',       // CADET ou SENIOR
        'adversaire_nom',  // Nom de l'équipe adverse
        'adversaire_code', // Code ASC de l'adversaire (si sur la plateforme)
        'lieu',            // Terrain / Stade
        'phase',           // Phase de Groupes, 1/4 Finale, 1/2 Finale, Finale
    ];

    protected function casts(): array {
        return [
            'date_match' => 'datetime',
            'started_at' => 'datetime',
            'second_half_started_at' => 'datetime',
        ];
    }

    protected $appends = ['homme_du_match'];

    public function getHommeDuMatchAttribute()
    {
        if ($this->statut !== 'TERMINE') return null;

        $topNote = $this->playerNotes()
            ->selectRaw('player_id, AVG(note) as average_note')
            ->groupBy('player_id')
            ->orderByDesc('average_note')
            ->first();

        if ($topNote) {
            $player = \App\Models\Player::find($topNote->player_id);
            return $player ? $player->prenom . ' ' . $player->nom : null;
        }

        return null;
    }

    public function asc() {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }

    public function opponent() {
        return $this->belongsTo(PouleTeam::class, 'poule_team_id');
    }

    public function convocations() {
        return $this->hasMany(Convocation::class);
    }

    public function playerNotes() {
        return $this->hasMany(PlayerNote::class);
    }

    public function events() {
        return $this->hasMany(MatchEvent::class)->with('player')->orderBy('minute', 'asc');
    }
}
