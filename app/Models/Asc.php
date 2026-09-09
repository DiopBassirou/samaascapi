<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asc extends Model
{
    protected $primaryKey = 'code_unique';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code_unique', 'nom', 'ville', 'zone', 'statut', 'president_id', 'recepisse_path', 'is_active', 'logo_path', 'cotisation_objectif'];

    public function users() {
        return $this->hasMany(User::class, 'asc_code', 'code_unique');
    }

    public function president() {
        return $this->belongsTo(User::class, 'president_id');
    }
}
