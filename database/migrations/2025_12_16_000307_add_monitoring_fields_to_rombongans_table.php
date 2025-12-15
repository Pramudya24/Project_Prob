<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->boolean('is_sent_to_monitoring')->default(false)->after('no_spm');
            $table->timestamp('tanggal_kirim_monitoring')->nullable()->after('is_sent_to_monitoring');
        });
    }

    public function down(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->dropColumn(['is_sent_to_monitoring', 'tanggal_kirim_monitoring']);
        });
    }
};