<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->string('nama_opd')->nullable()->after('nama_rombongan');
        });
    }

    public function down()
    {
        Schema::table('rombongans', function (Blueprint $table) {
            $table->dropColumn('nama_opd');
        });
    }
};
