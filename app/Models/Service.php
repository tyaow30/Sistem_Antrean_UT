<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    protected $guarded = ['id'];

    protected $fillable = [
        'nama_layanan',
        'deskripsi',
        'is_active',
        'loket_id', // Pastikan loket_id diizinkan untuk mass assignment
    ];

    // JIKA menggunakan kolom tunggal `loket_id` di tabel services (Relasi One-to-Many):
    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class, 'loket_id');
    }

    // (Opsional) Jika tetap ingin mempertahankan tabel pivot counter_services:
    public function lokets(): BelongsToMany
    {
        return $this->belongsToMany(Loket::class, 'counter_services', 'service_id', 'loket_id');
    }

    // Relasi ke Antrean
    public function antreanAwal(): HasMany
    {
        return $this->hasMany(Antrean::class, 'service_awal_id');
    }

    public function antreanAktual(): HasMany
    {
        return $this->hasMany(Antrean::class, 'service_aktual_id');
    }
}