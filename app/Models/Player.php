<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = ['asc_code', 'nom', 'poste', 'is_active'];

    public function asc() {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }

    public function convocations() {
        return $this->hasMany(Convocation::class);
    }

    public function notes() {
        return $this->hasMany(PlayerNote::class);
    }
}
