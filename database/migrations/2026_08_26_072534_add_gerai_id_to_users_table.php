<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan foreign key gerai_id, dibuat nullable untuk role admin utama jika ada
            $table->foreignId('gerai_id')->nullable()->after('id')->constrained('gerai')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gerai_id']);
            $table->dropColumn('gerai_id');
        });
    }
};