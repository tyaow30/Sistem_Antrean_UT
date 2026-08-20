<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loket extends Model
{
    protected $table = 'loket';
    protected $guarded = [];

    public function antreanAktif()
    {
        return $this->hasMany(Antrean::class, 'loket_asal_id');
    }
}

