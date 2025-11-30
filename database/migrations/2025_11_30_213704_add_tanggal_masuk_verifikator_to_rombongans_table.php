<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->timestamp('tanggal_masuk_verifikator')->nullable()->after('status_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk_verifikator');
        });
    }
};