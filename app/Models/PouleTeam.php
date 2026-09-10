<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PouleTeam extends Model
{
    protected $fillable = [
        'poule_id', 'nom_equipe', 'asc_code', 'joues', 'victoires', 'nuls', 
        'defaites', 'buts_pour', 'buts_contre', 'points'
    ];

    public function poule() {
        return $this->belongsTo(Poule::class);
    }

    public function matchGames() {
        return $this->hasMany(MatchGame::class);
    }

    public function asc() {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }
}
