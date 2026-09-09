<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asc_code',
        'role_id',
        'date_debut',
        'date_fin',
        'saison',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asc()
    {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
