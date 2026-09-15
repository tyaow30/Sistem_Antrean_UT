<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'services';
    protected $fillable = ['nama_layanan', 'deskripsi', 'is_active'];

    public function loket(): BelongsToMany
    {
        return $this->belongsToMany(Loket::class, 'counter_services', 'service_id', 'loket_id');
    }

    public function lokets(): BelongsToMany
    {
        return $this->belongsToMany(Loket::class, 'counter_services', 'service_id', 'loket_id');
    }
}