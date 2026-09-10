<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Loket extends Model
{
    use HasFactory;

    protected $table = 'loket';

    protected $fillable = [
        'gerai_id',
        'nomor_loket',
        'nama_loket',
        'status',
        'active_petugas_id',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
    ];

    // Relasi activePetugas yang dicari oleh AdminController
    public function activePetugas()
    {
        return $this->belongsTo(User::class, 'active_petugas_id');
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

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'counter_services', 'loket_id', 'service_id');
    }

    public function layanans()
{
    return $this->hasMany(Layanan::class);
}
}