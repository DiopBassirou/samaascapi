<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convocation extends Model
{
    protected $fillable = ['match_game_id', 'player_id', 'statut'];

    public function match() {
        return $this->belongsTo(MatchGame::class, 'match_game_id');
    }

    public function player() {
        return $this->belongsTo(Player::class);
    }
}
