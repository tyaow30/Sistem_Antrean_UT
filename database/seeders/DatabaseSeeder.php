<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Loket;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat 4 Loket Tetap (Tanpa Gerai)
        $loket1 = Loket::updateOrCreate(
            ['nomor_loket' => 1],
            ['nama_loket' => 'Loket 1', 'status' => 'INACTIVE', 'active_petugas_id' => null]
        );

        $loket2 = Loket::updateOrCreate(
            ['nomor_loket' => 2],
            ['nama_loket' => 'Loket 2', 'status' => 'INACTIVE', 'active_petugas_id' => null]
        );

        $loket3 = Loket::updateOrCreate(
            ['nomor_loket' => 3],
            ['nama_loket' => 'Loket 3', 'status' => 'INACTIVE', 'active_petugas_id' => null]
        );

        $loket4 = Loket::updateOrCreate(
            ['nomor_loket' => 4],
            ['nama_loket' => 'Loket 4', 'status' => 'INACTIVE', 'active_petugas_id' => null]
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
        // Petugas 1
        $petugas1 = User::updateOrCreate(
            ['email' => 'petugas1@gmail.com'],
            [
                'name' => 'Petugas Loket 1',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket1->id,
            ]
        );
        $loket1->update(['active_petugas_id' => $petugas1->id]);

        // Petugas 2
        $petugas2 = User::updateOrCreate(
            ['email' => 'petugas2@gmail.com'],
            [
                'name' => 'Petugas Loket 2',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket2->id,
            ]
        );
        $loket2->update(['active_petugas_id' => $petugas2->id]);

        // Petugas 3
        $petugas3 = User::updateOrCreate(
            ['email' => 'petugas3@gmail.com'],
            [
                'name' => 'Petugas Loket 3',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket3->id,
            ]
        );
        $loket3->update(['active_petugas_id' => $petugas3->id]);

        // Petugas 4
        $petugas4 = User::updateOrCreate(
            ['email' => 'petugas4@gmail.com'],
            [
                'name' => 'Petugas Loket 4',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket4->id,
            ]
        );
        $loket4->update(['active_petugas_id' => $petugas4->id]);


        // 4. Buat Daftar Layanan Sesuai Brief (Tanpa Deskripsi)
        // Parameter kedua dikosongkan [] karena kita hapus bagian deskripsi
        $layananPengurusanIjazah  = Service::updateOrCreate(['nama_layanan' => 'Pengurusan Ijazah'], []);
        $layananPengambilanIjazah = Service::updateOrCreate(['nama_layanan' => 'Pengambilan Ijazah'], []);
        $layananRegistrasi        = Service::updateOrCreate(['nama_layanan' => 'Registrasi'], []);
        $layananInformasi         = Service::updateOrCreate(['nama_layanan' => 'Informasi'], []);
        $layananUjian             = Service::updateOrCreate(['nama_layanan' => 'Ujian'], []);
        $layananLegalisir         = Service::updateOrCreate(['nama_layanan' => 'Legalisir'], []);
        $layananStempel           = Service::updateOrCreate(['nama_layanan' => 'Stempel'], []);
        $layananTandaTangan       = Service::updateOrCreate(['nama_layanan' => 'Tanda Tangan'], []);

        // 5. Petakan Relasi Layanan ke Loket (Counter Services)
        // Loket 1: Pengurusan & Pengambilan Ijazah
        $loket1->services()->sync([$layananPengurusanIjazah->id, $layananPengambilanIjazah->id]);

        // Loket 2 & 3 (Kompatibel): Registrasi, Informasi, Ujian
        $sharedServices = [$layananRegistrasi->id, $layananInformasi->id, $layananUjian->id];
        $loket2->services()->sync($sharedServices);
        $loket3->services()->sync($sharedServices);

        // Loket 4: Legalisir, Stempel, Tanda Tangan
        $loket4->services()->sync([$layananLegalisir->id, $layananStempel->id, $layananTandaTangan->id]);
    }
}