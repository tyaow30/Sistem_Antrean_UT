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
        // 1. Buat 4 Loket Tetap (Status awal 0 / INACTIVE dan active_petugas_id null)
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

        // 3. Buat Akun Petugas Loket 1 sampai 4 (Hanya assignment default di user)
        $petugas1 = User::updateOrCreate(
            ['email' => 'petugas1@gmail.com'],
            [
                'name' => 'Petugas Loket 1',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket1->id,
            ]
        );

        $petugas2 = User::updateOrCreate(
            ['email' => 'petugas2@gmail.com'],
            [
                'name' => 'Petugas Loket 2',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket2->id,
            ]
        );

        $petugas3 = User::updateOrCreate(
            ['email' => 'petugas3@gmail.com'],
            [
                'name' => 'Petugas Loket 3',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket3->id,
            ]
        );

        $petugas4 = User::updateOrCreate(
            ['email' => 'petugas4@gmail.com'],
            [
                'name' => 'Petugas Loket 4',
                'password' => Hash::make('password123'),
                'role' => 'PETUGAS',
                'assigned_loket_id' => $loket4->id,
            ]
        );

        // 4. Buat Daftar Layanan
        $layananPengurusanIjazah  = Service::updateOrCreate(['nama_layanan' => 'Pengurusan Ijazah'], []);
        $layananPengambilanIjazah = Service::updateOrCreate(['nama_layanan' => 'Pengambilan Ijazah'], []);
        $layananRegistrasi        = Service::updateOrCreate(['nama_layanan' => 'Registrasi'], []);
        $layananInformasi         = Service::updateOrCreate(['nama_layanan' => 'Informasi'], []);
        $layananUjian             = Service::updateOrCreate(['nama_layanan' => 'Ujian'], []);
        $layananLegalisir         = Service::updateOrCreate(['nama_layanan' => 'Legalisir'], []);
        $layananStempel           = Service::updateOrCreate(['nama_layanan' => 'Stempel'], []);
        $layananTandaTangan       = Service::updateOrCreate(['nama_layanan' => 'Tanda Tangan'], []);

        // 5. Petakan Relasi Layanan ke Loket
        $loket1->services()->sync([$layananPengurusanIjazah->id, $layananPengambilanIjazah->id]);

        $sharedServices = [$layananRegistrasi->id, $layananInformasi->id, $layananUjian->id];
        $loket2->services()->sync($sharedServices);
        $loket3->services()->sync($sharedServices);

        $loket4->services()->sync([$layananLegalisir->id, $layananStempel->id, $layananTandaTangan->id]);
    }
}