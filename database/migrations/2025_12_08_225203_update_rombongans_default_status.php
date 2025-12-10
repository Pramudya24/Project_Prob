<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->string('status_pengiriman')->default('Belum Dikirim')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->string('status_pengiriman')->default(null)->change();
        });
    }
};