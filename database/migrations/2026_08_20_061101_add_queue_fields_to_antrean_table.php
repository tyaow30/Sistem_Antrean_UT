<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrean', function (Blueprint $table) {
            $table->string('kode_antrean')->after('nomor_antrean');

            $table->timestamp('waktu_ambil')->nullable()->after('tanggal');
            $table->timestamp('waktu_panggil')->nullable()->after('waktu_ambil');
            $table->timestamp('waktu_layani')->nullable()->after('waktu_panggil');
            $table->timestamp('waktu_selesai')->nullable()->after('waktu_layani');
        });

        DB::statement("
            ALTER TABLE antrean
            MODIFY status ENUM(
                'WAITING',
                'CALLED',
                'SERVING',
                'DONE',
                'SKIPPED'
            ) NOT NULL DEFAULT 'WAITING'
        ");
    }

    public function down(): void
    {
        Schema::table('antrean', function (Blueprint $table) {
            $table->dropColumn([
                'kode_antrean',
                'waktu_ambil',
                'waktu_panggil',
                'waktu_layani',
                'waktu_selesai',
            ]);
        });

        DB::statement("
            ALTER TABLE antrean
            MODIFY status ENUM(
                'WAITING',
                'CALLING',
                'SERVING',
                'DONE',
                'SKIPPED'
            ) NOT NULL DEFAULT 'WAITING'
        ");
    }
};