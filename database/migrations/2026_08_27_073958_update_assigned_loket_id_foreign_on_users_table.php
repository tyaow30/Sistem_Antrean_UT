<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus constraint lama
            $table->dropForeign(['assigned_loket_id']);

            // Tambahkan kembali dengan nullOnDelete
            $table->foreign('assigned_loket_id')
                  ->references('id')
                  ->on('loket')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['assigned_loket_id']);
            $table->foreign('assigned_loket_id')
                  ->references('id')
                  ->on('loket');
        });
    }
};