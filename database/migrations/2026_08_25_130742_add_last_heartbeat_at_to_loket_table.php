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
        Schema::table('loket', function (Blueprint $table) {
            $table->timestamp('last_heartbeat_at')
                ->nullable()
                ->after('active_petugas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loket', function (Blueprint $table) {
            $table->dropColumn('last_heartbeat_at');
        });
    }
};