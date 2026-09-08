<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layanan extends Model
{
    use HasFactory;

        protected $table = 'layanans';
        protected $fillable = ['loket_id', 'nama_layanan', 'deskripsi'];

        // Relasi ke Loket
        public function loket()
        {
            return $this->belongsTo(Loket::class);
        }
    }
