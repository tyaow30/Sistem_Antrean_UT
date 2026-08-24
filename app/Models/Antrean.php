<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrean extends Model
{
    protected $table = 'antrean';
    protected $guarded = [];

    public function sesiHari()
    {
        return $this->belongsTo(SesiHari::class, 'sesi_hari_id');
    }

    public function gerai()
    {
        return $this->belongsTo(Gerai::class, 'gerai_id');
    }

    public function loketAsal()
    {
        return $this->belongsTo(Loket::class, 'loket_asal_id');
    }

    public function loketMelayani()
    {
        return $this->belongsTo(Loket::class, 'loket_melayani_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}