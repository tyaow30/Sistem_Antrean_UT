<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gerai;
use App\Models\Loket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Gerai Sample
        $gerai = Gerai::create([
            'nama_gerai' => 'Gerai Utama',
            'is_active' => true,
        ]);

        // Loket Sample
        $loket1 = Loket::create([
            'gerai_id' => $gerai->id,
            'nomor_loket' => 1,
            'status' => 'ACTIVE',
        ]);

        $loket2 = Loket::create([
            'gerai_id' => $gerai->id,
            'nomor_loket' => 2,
            'status' => 'ACTIVE',
        ]);

        // Akun Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
        ]);

        // Akun Petugas Loket 1
        User::create([
            'name' => 'Budi Petugas',
            'email' => 'petugas1@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'PETUGAS',
            'assigned_gerai_id' => $gerai->id,
            'assigned_loket_id' => $loket1->id,
        ]);
    }
}