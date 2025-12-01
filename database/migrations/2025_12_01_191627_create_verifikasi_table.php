<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('verifikasi', function (Blueprint $table) {
            $table->id();
            // Tambahkan kolom sesuai kebutuhan, contoh:
            $table->string('nama');
            $table->string('status')->default('pending');
            $table->text('keterangan')->nullable();
            // dst...
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('verifikasi');
    }
};