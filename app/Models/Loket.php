<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loket extends Model
{
    protected $table = 'loket';

    protected $fillable = [
        'gerai_id',
        'nomor_loket',
        'status',
        'active_petugas_id',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
    ];

    public function gerai()
    {
        return $this->belongsTo(Gerai::class);
    }

    public function petugasAktif()
    {
        return $this->belongsTo(User::class, 'active_petugas_id');
    }

    public function antrean()
    {
        return $this->hasMany(Antrean::class, 'loket_asal_id');
    }

    public function antreanDilayani()
    {
        return $this->hasMany(Antrean::class, 'loket_melayani_id');
    }
}