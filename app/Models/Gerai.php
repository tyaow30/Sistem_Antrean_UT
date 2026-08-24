<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gerai extends Model
{
    use HasFactory;

    protected $table = 'gerai';

    protected $fillable = [
        'nama_gerai',
        'is_active',
    ];

    /**
     * Relasi 1 Gerai punya Banyak Loket
     */
    public function loket()
    {
        return $this->hasMany(Loket::class, 'gerai_id');
    }
}