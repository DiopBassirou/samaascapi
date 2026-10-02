<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    protected $fillable = [
        'asc_code', 'poule_team_id', 'date_match',
        'score_asc', 'score_adv', 'penalties_asc', 'penalties_adv', 'statut', 'started_at', 'second_half_started_at',
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

    protected $appends = ['homme_du_match', 'team_a_name', 'team_a_logo', 'team_b_name', 'team_b_logo', 'score_asc_display', 'score_adv_display', 'penalties_asc_display', 'penalties_adv_display'];

    public function getTeamANameAttribute() {
        return $this->asc ? $this->asc->nom : 'Equipe A';
    }

    public function getTeamALogoAttribute() {
        return $this->asc ? $this->asc->logo_url : null;
    }

    public function getTeamBNameAttribute() {
        if ($this->opponent) {
            if ($this->opponent->asc_code) {
                return $this->opponent->asc ? $this->opponent->asc->nom : $this->opponent->nom_equipe;
            }
            return $this->opponent->nom_equipe ?? 'Equipe B';
        }
        if ($this->adversaire_code) {
            $adv = \App\Models\Asc::where('code_unique', $this->adversaire_code)->first();
            return $adv ? $adv->nom : 'Equipe B';
        }
        if ($this->adversaire_nom) {
            return $this->adversaire_nom;
        }
        return 'Equipe B';
    }

    public function getTeamBLogoAttribute() {
        if ($this->opponent) {
            if ($this->opponent->asc_code && $this->opponent->asc) {
                return $this->opponent->asc->logo_url;
            }
            return $this->opponent->logo ?? $this->opponent->logo_url ?? null;
        }
        if ($this->adversaire_code) {
            $adv = \App\Models\Asc::where('code_unique', $this->adversaire_code)->first();
            return $adv ? $adv->logo_url : null;
        }
        return null;
    }

    public function getScoreAscDisplayAttribute() { return $this->score_asc; }
    public function getScoreAdvDisplayAttribute() { return $this->score_adv; }
    public function getPenaltiesAscDisplayAttribute() { return $this->penalties_asc; }
    public function getPenaltiesAdvDisplayAttribute() { return $this->penalties_adv; }

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
