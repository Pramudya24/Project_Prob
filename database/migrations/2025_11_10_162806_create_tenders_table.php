<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal_dibuat');
            // 1. Nama Pekerjaan
            $table->string('nama_pekerjaan');

            // 2. Kode RUP
            $table->string('kode_rup');

            // 3. Pagu RUP (nominal)
            $table->bigInteger('pagu_rup');
            // 4. Kode Paket
            $table->string('kode_paket')->nullable();

            // 5. Jenis Pengadaan (dropdown)
            $table->enum('jenis_pengadaan', [
                'Barang',
                'Pekerjaan Konstruksi',
                'Jasa Konsultansi',
                'Jasa Lainnya',
                'Terintegrasi'
            ])->nullable();

            // 6. Upload Surat Pesanan (file path)
            $table->string('summary_report')->nullable();

            // 7. Nilai Kontrak (nominal)
            $table->decimal('nilai_kontrak', 20, 2)->nullable();

            // 8. Nilai PDN/TKDN/IMPOR (dropdown)
            $table->enum('pdn_tkdn_impor', [
                'PDN',
                'TKDN',
                'IMPOR'
            ])->nullable();

            //  Nilai  (nominal)
            $table->decimal('nilai_pdn_tkdn_impor', 20, 2)->nullable();

            // 9. Nilai UMK_Non UMK (dropdown)
            $table->enum('umk_non_umk', [
                'UMK',
                'Non UMK'
            ])->nullable();

            //  Nilai  (nominal)
            $table->decimal('nilai_umk', 20, 2)->nullable();
            
            // 10. Serah Terima Pekerjaan (dropdown)
            $table->enum('serah_terima_pekerjaan', [
                'BAST',
                'On Progres'
            ])->nullable();
            $table->string('BAST')->nullable();
            // 11. Penilaian Kinerja
            $table->enum('penilaian_kinerja', [
            'Baik Sekali',
            'Baik',
            'Cukup',
            'Buruk',
            'Belum Dinilai',
            ])->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
