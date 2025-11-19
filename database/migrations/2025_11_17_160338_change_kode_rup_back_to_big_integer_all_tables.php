<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kode_rup kembali ke bigInteger di semua tabel
        
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });

        Schema::table('nontenders', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });

        Schema::table('swakelolas', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });

        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });

        Schema::table('pls', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });

        Schema::table('tenders', function (Blueprint $table) {
            $table->bigInteger('kode_rup')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan ke string jika rollback
        
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });

        Schema::table('nontenders', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });

        Schema::table('swakelolas', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });

        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });

        Schema::table('pls', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });

        Schema::table('tenders', function (Blueprint $table) {
            $table->string('kode_rup')->change();
        });
    }
};