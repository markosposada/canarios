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
        Schema::table('taxistas', function (Blueprint $table) {
    $table->dropUnique(['movil']);
    $table->dropUnique(['placa_taxi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxistas', function (Blueprint $table) {
            //
        });
    }
};
