<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $table = 'layanans';

    protected $fillable = [
        'nama_layanan',
        'deskripsi',
        'loket_id',
        'is_active',
    ];

    /**
     * Relasi ke Loket (One-to-Many: 1 Layanan milik 1 Loket)
     */
    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class, 'loket_id');
    }
}