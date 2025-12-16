<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah ke tabel nontenders
        Schema::table('nontenders', function (Blueprint $table) {
            $table->decimal('persentase_tkdn', 5, 2)->nullable()->after('pdn_tkdn_impor');
        });

        Schema::table('epurcasing', function (Blueprint $table) {
            $table->decimal('persentase_tkdn', 5, 2)->nullable()->after('pdn_tkdn_impor');
        });

        // Tambah ke tabel pengadaan_darurat
        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->decimal('persentase_tkdn', 5, 2)->nullable()->after('pdn_tkdn_impor');
        });

        // Tambah ke tabel tenders
        Schema::table('tenders', function (Blueprint $table) {
            $table->decimal('persentase_tkdn', 5, 2)->nullable()->after('pdn_tkdn_impor');
        });
    }

    public function down(): void
    {
        Schema::table('nontenders', function (Blueprint $table) {
            $table->dropColumn('persentase_tkdn');
        });

        Schema::table('epurcasing', function (Blueprint $table) {
            $table->dropColumn('persentase_tkdn');
        });

        Schema::table('pengadaan_darurat', function (Blueprint $table) {
            $table->dropColumn('persentase_tkdn');
        });

        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn('persentase_tkdn');
        });
    }
};

