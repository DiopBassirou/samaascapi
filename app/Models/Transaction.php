<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'montant', 'categorie', 'date_transaction'
    ];

    protected function casts(): array {
        return [
            'date_transaction' => 'date',
        ];
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
