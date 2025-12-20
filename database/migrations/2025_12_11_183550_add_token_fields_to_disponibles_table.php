<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disponibles', function (Blueprint $table) {
            // Token corto tipo A07 (1 letra + 2 números)
            $table->string('dis_token', 3)->nullable()->after('dis_usuario');

            // Fecha/hora de vencimiento del token
            $table->dateTime('dis_token_expires_at')->nullable()->after('dis_token');
        });
    }

    public function down(): void
    {
        Schema::table('disponibles', function (Blueprint $table) {
            $table->dropColumn(['dis_token', 'dis_token_expires_at']);
        });
    }
};
