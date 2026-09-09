<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_game_id',
        'player_id',
        'type',
        'minute',
        'description',
    ];

    public function matchGame()
    {
        return $this->belongsTo(MatchGame::class, 'match_game_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }
}
