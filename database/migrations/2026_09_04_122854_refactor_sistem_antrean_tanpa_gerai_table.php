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
        // Matikan sementara pengecekan foreign key agar aman saat drop tabel
        Schema::disableForeignKeyConstraints();

        // 1. Hapus tabel-tabel lama
        Schema::dropIfExists('antrean');
        Schema::dropIfExists('counter_services');
        Schema::dropIfExists('services');
        Schema::dropIfExists('loket');
        Schema::dropIfExists('gerai');

        // 2. Bersihkan kolom relasi lama pada tabel users jika ada
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'assigned_loket_id')) {
                    $table->dropForeign(['assigned_loket_id']);
                    $table->dropColumn('assigned_loket_id');
                }
                if (Schema::hasColumn('users', 'assigned_gerai_id')) {
                    $table->dropForeign(['assigned_gerai_id']);
                    $table->dropColumn('assigned_gerai_id');
                }
                if (Schema::hasColumn('users', 'gerai_id')) {
                    $table->dropForeign(['gerai_id']);
                    $table->dropColumn('gerai_id');
                }
            });
        }

        // Aktifkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();

        // 3. Buat kembali tabel LOKET yang baru (Hanya 4 Loket tetap tanpa Gerai)
        Schema::create('loket', function (Blueprint $table) {
            $table->id();
            $table->string('nama_loket'); // Contoh: Loket 1, Loket 2, dll.
            $table->integer('nomor_loket')->unique(); // 1, 2, 3, 4
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BREAK'])->default('INACTIVE');
            $table->foreignId('active_petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        // 4. Kembalikan kolom assigned_loket_id pada tabel users dengan relasi ke loket baru
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['ADMIN', 'PETUGAS'])->default('PETUGAS')->after('email');
            }
            if (!Schema::hasColumn('users', 'assigned_loket_id')) {
                $table->foreignId('assigned_loket_id')->nullable()->constrained('loket')->nullOnDelete();
            }
        });

        // 5. Buat tabel SERVICES (Layanan)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nama_layanan'); // Contoh: Pengambilan Ijazah, Registrasi, dll.
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Buat tabel pivot COUNTER_SERVICES (Relasi Many-to-Many Layanan <-> Loket)
        Schema::create('counter_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loket_id')->constrained('loket')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->timestamps();
        });

        // 7. Buat tabel ANTREAN yang baru sesuai brief
        Schema::create('antrean', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_antrean'); 
            
            // Data Pelanggan dari Kiosk
            $table->string('nama');
            $table->string('nim')->nullable();
            $table->string('nip')->nullable();

            // Relasi Layanan & Loket (Awal vs Aktual untuk fitur koreksi/pindah)
            $table->foreignId('service_awal_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('service_aktual_id')->nullable()->constrained('services')->nullOnDelete();
            
            $table->foreignId('loket_asal_id')->constrained('loket')->onDelete('cascade'); 
            $table->foreignId('loket_pelayanan_id')->nullable()->constrained('loket')->nullOnDelete(); 
            
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->enum('status', ['WAITING', 'PRINTING', 'CALLED', 'SERVING', 'DONE', 'SKIPPED', 'PENDING'])->default('WAITING');
            $table->enum('status_penyelesaian', ['SELESAI', 'PENDING', 'BELUM DITINDAKLANJUTI'])->default('BELUM DITINDAKLANJUTI');
            
            $table->date('tanggal');
            
            $table->timestamp('waktu_ambil')->nullable();
            $table->timestamp('waktu_dipanggil')->nullable();
            $table->timestamp('waktu_dilayani')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            
            $table->timestamps();
        });

        // 8. Tabel Sesi Hari
        if (!Schema::hasTable('sesi_hari')) {
            Schema::create('sesi_hari', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->unique();
                $table->boolean('is_open')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('antrean');
        Schema::dropIfExists('counter_services');
        Schema::dropIfExists('services');
        
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'assigned_loket_id')) {
                    $table->dropForeign(['assigned_loket_id']);
                    $table->dropColumn('assigned_loket_id');
                }
            });
        }

        Schema::dropIfExists('loket');
        Schema::dropIfExists('sesi_hari');
        Schema::enableForeignKeyConstraints();
    }
};