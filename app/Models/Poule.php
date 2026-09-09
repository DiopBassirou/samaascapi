<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poule extends Model
{
    protected $fillable = ['asc_code', 'nom'];

    public function asc() {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }

    public function teams() {
        return $this->hasMany(PouleTeam::class);
    }
}
