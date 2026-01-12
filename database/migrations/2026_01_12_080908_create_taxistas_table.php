<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taxistas', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100);
            $table->string('apellidos', 150);

            $table->string('cedula', 30)->unique();

            $table->string('celular', 30);
            $table->string('movil', 20)->unique();

            $table->string('placa_taxi', 20)->unique();

            // Opcional (puede quedar NULL) y único si se registra
            $table->string('correo_electronico', 150)->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxistas');
    }
};
