<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntreanLog extends Model
{
    protected $table = 'antrean_logs';
        protected $guarded = ['id'];

        public function antrean() {
            return $this->belongsTo(Antrean::class);
        }

        public function loket() {
            return $this->belongsTo(Loket::class);
    }}
