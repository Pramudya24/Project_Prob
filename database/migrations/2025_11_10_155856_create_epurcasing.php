<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu apakah table sudah ada
        if (!Schema::hasTable('epurcasing')) {
            Schema::create('epurcasing', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('tanggal_dibuat');
                $table->string('nama_pekerjaan');
                $table->string('kode_rup');
                $table->bigInteger('pagu_rup');
                $table->string('kode_paket')->nullable();
                $table->enum('jenis_pengadaan', [
                    'Barang',
                    'Pekerjaan Konstruksi', 
                    'Jasa Konsultansi',
                    'Jasa Lainnya',
                    'Terintegrasi'
                ])->nullable();
                $table->string('metode_pengadaan')->nullable();
                $table->string('surat_pesanan')->nullable();
                $table->decimal('nilai_kontrak', 20, 2)->nullable();
                $table->enum('pdn_tkdn_impor', [
                    'PDN',
                    'TKDN',
                    'IMPOR'
                ])->nullable();
                $table->decimal('nilai_pdn_tkdn_impor', 20, 2)->nullable();
                $table->enum('umk_non_umk', [
                    'UMK',
                    'Non UMK'
                ])->nullable();
                $table->decimal('nilai_umk', 20, 2)->nullable();
                $table->enum('serah_terima', [
                    'BAST',
                    'On Progres'
                ])->nullable();
                $table->string('BAST')->nullable();
                $table->text('penilaian_kinerja')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('epurcasing');
    }
};