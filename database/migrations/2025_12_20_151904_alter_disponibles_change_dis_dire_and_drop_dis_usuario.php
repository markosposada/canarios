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
        Schema::table('disponibles', function (Blueprint $table) {
            // Ejemplo: cambiar tipo de dis_dire (ajusta según lo que quieras hacer)
            // $table->string('dis_dire', 255)->change();

            // Eliminar columna dis_usuario
            $table->dropColumn('dis_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disponibles', function (Blueprint $table) {
            // Revertir el cambio de dis_dire si lo cambiaste
            // $table->text('dis_dire')->change();

            // Volver a crear la columna dis_usuario exactamente como estaba
            $table->text('dis_usuario');
        });
    }
};
