<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'layanans';
    protected $fillable = ['loket_id', 'nama_layanan', 'deskripsi'];

    // Relasi One-to-Many ke Loket (karena ada kolom loket_id di tabel layanans)
    public function loket()
    {
        return $this->belongsTo(Loket::class, 'loket_id');
    }

    // Alias jamak jika dibutuhkan di tempat lain
    public function lokets()
    {
        return $this->belongsTo(Loket::class, 'loket_id');
    }
}