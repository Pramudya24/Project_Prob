<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rombongan_item_field_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rombongan_item_id')->constrained('rombongan_items')->onDelete('cascade');
            $table->string('field_name', 100)->comment('Nama field yang diverifikasi: nama_pekerjaan, kode_rup, dll');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('keterangan')->nullable()->comment('Catatan khusus per field');
            $table->timestamps();

            // Unique constraint dengan nama pendek
            $table->unique(['rombongan_item_id', 'field_name'], 'idx_unique_field_verif');
            
            // Index dengan nama pendek
            $table->index(['rombongan_item_id', 'is_verified'], 'idx_item_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rombongan_item_field_verifications');
    }
};