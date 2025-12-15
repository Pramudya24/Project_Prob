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
            $table->string('no_spm', 100)->nullable()->after('status_pengiriman');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->dropColumn('no_spm');
        });
    }
};