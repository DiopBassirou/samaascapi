<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppDevice extends Model
{
    protected $fillable = [
        'device_uuid',
        'fcm_token',
        'asc_code',
        'platform',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function asc()
    {
        return $this->belongsTo(Asc::class, 'asc_code', 'code_unique');
    }
}
