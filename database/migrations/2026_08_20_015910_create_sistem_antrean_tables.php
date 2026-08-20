<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gerai', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gerai');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerai_id')->constrained('gerai')->onDelete('cascade');
            $table->integer('nomor_loket');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BREAK'])->default('INACTIVE');
            $table->unsignedBigInteger('active_petugas_id')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['ADMIN', 'PETUGAS'])->default('PETUGAS')->after('email');
            $table->foreignId('assigned_gerai_id')->nullable()->constrained('gerai')->after('role');
            $table->foreignId('assigned_loket_id')->nullable()->constrained('loket')->after('assigned_gerai_id');
        });

        Schema::create('antrean', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('kode_antrean');
            $table->integer('nomor_urut');
            $table->foreignId('gerai_id')->constrained('gerai');
            $table->foreignId('loket_asal_id')->constrained('loket');
            $table->foreignId('loket_melayani_id')->nullable()->constrained('loket');
            $table->foreignId('petugas_id')->nullable()->constrained('users');
            $table->enum('status', ['WAITING', 'CALLED', 'SERVING', 'DONE', 'SKIPPED'])->default('WAITING');
            $table->timestamps();
        });

        Schema::create('sesi_hari', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sistem_antrean_tables');
    }
};
