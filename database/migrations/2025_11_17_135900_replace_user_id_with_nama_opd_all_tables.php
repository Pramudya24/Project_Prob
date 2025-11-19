<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TAMBAH kolom nama_opd dulu di semua tabel
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });
        
        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });
        
        Schema::table('pls', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });
        
        Schema::table('nontenders', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });
        
        Schema::table('swakelolas', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });
        
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('nama_opd', 50)->nullable()->after('id');
        });

        // 2. Hapus user_id setelah kolom dibuat
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('pls', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        // 1. Kembalikan user_id dulu
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        Schema::table('pls', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        // 2. Hapus kolom nama_opd dari semua tabel
        Schema::table('epurcasing', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
        
        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
        
        Schema::table('pls', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
        
        Schema::table('nontenders', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
        
        Schema::table('swakelolas', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
        
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
    }
};