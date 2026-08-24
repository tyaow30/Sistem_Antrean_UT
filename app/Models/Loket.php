<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loket extends Model
{
    protected $table = 'loket';
    protected $guarded = [];

    public function gerai()
    {
        return $this->belongsTo(Gerai::class, 'gerai_id');
    }

    public function petugasAktif()
    {
        return $this->belongsTo(User::class, 'active_petugas_id');
    }

    public function antreanAsal()
    {
        return $this->hasMany(Antrean::class, 'loket_asal_id');
    }

    public function antreanDilayani()
    {
        return $this->hasMany(Antrean::class, 'loket_melayani_id');
    }
}