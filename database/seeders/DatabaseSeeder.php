<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Loket;
use App\Models\Layanan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat 4 Loket Tetap
        $loket1 = Loket::updateOrCreate(
            ['nomor_loket' => 1],
            ['nama_loket' => 'Loket 1', 'status' => '0', 'active_petugas_id' => null]
        );

        $loket2 = Loket::updateOrCreate(
            ['nomor_loket' => 2],
            ['nama_loket' => 'Loket 2', 'status' => '0', 'active_petugas_id' => null]
        );

        $loket3 = Loket::updateOrCreate(
            ['nomor_loket' => 3],
            ['nama_loket' => 'Loket 3', 'status' => '0', 'active_petugas_id' => null]
        );

        $loket4 = Loket::updateOrCreate(
            ['nomor_loket' => 4],
            ['nama_loket' => 'Loket 4', 'status' => '0', 'active_petugas_id' => null]
        );

        // 2. Buat Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'role' => 'ADMIN',
                'assigned_loket_id' => null,
            ]
        );

        // 3. Buat Akun Petugas Loket 1 sampai 4
        User::updateOrCreate(
            ['email' => 'petugas1@gmail.com'],
            [
                'name' => 'Petugas Loket 1',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket1->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas2@gmail.com'],
            [
                'name' => 'Petugas Loket 2',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket2->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas3@gmail.com'],
            [
                'name' => 'Petugas Loket 3',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket3->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'petugas4@gmail.com'],
            [
                'name' => 'Petugas Loket 4',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket4->id,
            ]
        );

        // 4. Buat Daftar Layanan Sesuai Catatan Excel
        // Kelompok Atas (Untuk Loket 2 & 3)
        $sAdmReg       = Layanan::firstOrCreate(['nama_layanan' => 'Admisi/Registrasi']);
        $sPembayaran   = Layanan::firstOrCreate(['nama_layanan' => 'Pembayaran']);
        $sAkademik     = Layanan::firstOrCreate(['nama_layanan' => 'Akademik']);
        $sUjian        = Layanan::firstOrCreate(['nama_layanan' => 'Ujian']);
        $sLayananOnline= Layanan::firstOrCreate(['nama_layanan' => 'Layanan Online']);
        $sSalut        = Layanan::firstOrCreate(['nama_layanan' => 'SALUT']);
        $sAdministrasi = Layanan::firstOrCreate(['nama_layanan' => 'Administrasi']);
        $sInformasi    = Layanan::firstOrCreate(['nama_layanan' => 'Informasi']);
        $sLainnya      = Layanan::firstOrCreate(['nama_layanan' => 'Lainnya']);

        // Kelompok Tengah (Untuk Loket 4)
        $sLegalisir    = Layanan::firstOrCreate(['nama_layanan' => 'Legalisir']);
        $sTtdSurat     = Layanan::firstOrCreate(['nama_layanan' => 'Tanda Tangan Surat']);
        $sTtdLainnya   = Layanan::firstOrCreate(['nama_layanan' => 'Tanda Tangan Lainnya']);

        // Kelompok Bawah (Untuk Loket 1)
        $sIjazah       = Layanan::firstOrCreate(['nama_layanan' => 'Ijazah']);
        $sYudisium     = Layanan::firstOrCreate(['nama_layanan' => 'Yudisium']);
        $sWisuda       = Layanan::firstOrCreate(['nama_layanan' => 'Wisuda']);
        $sRalatIjazah  = Layanan::firstOrCreate(['nama_layanan' => 'Ralat Ijazah']);
        $sSuratRekom   = Layanan::firstOrCreate(['nama_layanan' => 'Surat Rekomendasi']);

        // 5. Petakan Relasi Layanan ke Loket Menggunakan sync()
        // Loket 2 & 3 (Shared Services - Kelompok Atas)
        $sharedAtas = [
            $sAdmReg->id, $sPembayaran->id, $sAkademik->id, 
            $sUjian->id, $sLayananOnline->id, $sSalut->id, 
            $sAdministrasi->id, $sInformasi->id, $sLainnya->id
        ];
        $loket2->services()->sync($sharedAtas);
        $loket3->services()->sync($sharedAtas);

        // Loket 4 (Kelompok Tengah)
        $loket4->services()->sync([
            $sLegalisir->id, $sTtdSurat->id, $sTtdLainnya->id
        ]);

        // Loket 1 (Kelompok Bawah)
        $loket1->services()->sync([
            $sIjazah->id, $sYudisium->id, $sWisuda->id, 
            $sRalatIjazah->id, $sSuratRekom->id
        ]);
    }
}   