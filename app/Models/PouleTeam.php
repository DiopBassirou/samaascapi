<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PouleTeam extends Model
{
    protected $fillable = [
        'poule_id', 'nom_equipe', 'joues', 'victoires', 'nuls', 
        'defaites', 'buts_pour', 'buts_contre', 'points'
    ];

    public function poule() {
        return $this->belongsTo(Poule::class);
    }

    public function matchGames() {
        return $this->hasMany(MatchGame::class);
    }
}
