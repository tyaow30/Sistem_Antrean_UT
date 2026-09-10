<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Antrean;
use Carbon\Carbon;

class AutoDeleteOldAntrean extends Command
{
    protected $signature = 'antrean:purge-old';
    protected $description = 'Menghapus data rekap antrean yang sudah berusia lebih dari 2 bulan (60 hari)';

    public function handle()
    {
        $twoMonthsAgo = Carbon::now()->subMonths(3)->toDateString();
        $deleted = Antrean::where('tanggal', '<', $twoMonthsAgo)->delete();

        $this->info("Berhasil menghapus {$deleted} data antrean lama.");
    }
}
