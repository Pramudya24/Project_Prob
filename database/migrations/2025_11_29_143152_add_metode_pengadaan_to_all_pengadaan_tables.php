<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom metode_pengadaan ke semua tabel
        $tables = [
            'nontenders',
            'pls',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('metode_pengadaan')->nullable()->after('jenis_pengadaan');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'nontenders',
            'pls',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('metode_pengadaan');
            });
        }
    }
};