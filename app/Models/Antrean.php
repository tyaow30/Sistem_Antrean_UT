<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SesiHari;
use App\Models\Loket;
use App\Models\User;
use App\Models\Service;

class Antrean extends Model
{
    protected $table = 'antreans';
    
    protected $fillable = [
        'tanggal',
        'loket_asal_id',
        'loket_pelayanan_id',
        'service_awal_id',
        'service_aktual_id',
        'petugas_id',
        'nomor_antrean',
        'status',
        'waktu_ambil',
        'nama',
        'nim',
        'no_hp',
        'kendala',
    ];

    public function sesiHari()
    {
        return $this->belongsTo(SesiHari::class, 'sesi_hari_id');
    }

    public function loketAsal()
    {
        return $this->belongsTo(Loket::class, 'loket_asal_id');
    }

    public function loketPelayanan()
    {
        return $this->belongsTo(Loket::class, 'loket_pelayanan_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function serviceAwal()
    {
        return $this->belongsTo(Service::class, 'service_awal_id');
    }

    public function serviceAktual()
    {
        return $this->belongsTo(Service::class, 'service_aktual_id');
    }
}