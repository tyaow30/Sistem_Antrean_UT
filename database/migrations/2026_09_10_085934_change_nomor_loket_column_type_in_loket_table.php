<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loket', function (Blueprint $table) {
            $table->string('nomor_loket')->change();
        });
    }

    public function down(): void
    {
        Schema::table('loket', function (Blueprint $table) {
            $table->integer('nomor_loket')->change();
        });
    }
};