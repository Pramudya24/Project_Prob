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
        Schema::table('rombongans', function (Blueprint $table) {
            $table->enum('status_verifikasi', ['Belum', 'Sudah'])->default('Belum')->after('nama_rombongan');
            $table->text('keterangan_verifikasi')->nullable()->after('status_verifikasi');
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->after('keterangan_verifikasi');
            $table->timestamp('tanggal_verifikasi')->nullable()->after('verifikator_id');
            $table->boolean('lolos_verif')->default(false)->after('tanggal_verifikasi');
            $table->enum('status_pengiriman', ['Belum Dikirim', 'Terkirim ke Verifikator', 'Revisi', 'Dikirim ke SPM'])->default('Belum Dikirim')->after('lolos_verif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->dropColumn([
                'status_verifikasi',
                'keterangan_verifikasi',
                'verifikator_id',
                'tanggal_verifikasi',
                'lolos_verif',
                'status_pengiriman'
            ]);
        });
    }
};