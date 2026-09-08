<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'nama_layanan',
        'deskripsi',
        'is_active',
    ];

    /**
     * Relasi ke Loket (Many-to-Many melalui tabel pivot counter_services)
     */
    public function lokets(): BelongsToMany
    {
        return $this->belongsToMany(Loket::class, 'counter_services', 'service_id', 'loket_id');
    }
}