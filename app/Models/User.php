<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'asc_code',
        'role_id',
        'telephone',
        'nom',
        'prenom',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function asc() {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
}
