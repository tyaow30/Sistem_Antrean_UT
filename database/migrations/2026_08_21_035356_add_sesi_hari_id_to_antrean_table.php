<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan di sini 'antrean' (tanpa s)
        Schema::table('antrean', function (Blueprint $table) {
            $table->foreignId('sesi_hari_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('sesi_hari')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('antrean', function (Blueprint $table) {
            $table->dropForeign(['sesi_hari_id']);
            $table->dropColumn('sesi_hari_id');
        });
    }
};