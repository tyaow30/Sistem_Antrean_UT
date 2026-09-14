<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Antrean;
use Carbon\Carbon;

class AutoDeleteOldAntrean extends Command
{
    protected $signature = 'antrean:purge-old';
    protected $description = 'Menghapus data rekap antrean yang sudah berusia lebih dari 1 tahun';

    public function handle()
    {
        // Mengubah durasi menjadi 1 tahun ke belakang
        $oneYearAgo = Carbon::now()->subYear()->toDateString();
        
        $deleted = Antrean::where('tanggal', '<', $oneYearAgo)->delete();

        $this->info("Berhasil menghapus {$deleted} data antrean lama yang berusia lebih dari 1 tahun.");
    }
}