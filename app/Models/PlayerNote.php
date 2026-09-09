<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerNote extends Model
{
    protected $fillable = ['match_game_id', 'player_id', 'vote_hash', 'note'];

    public function match() {
        return $this->belongsTo(MatchGame::class, 'match_game_id');
    }

    public function player() {
        return $this->belongsTo(Player::class);
    }
}
