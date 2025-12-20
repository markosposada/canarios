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
        DB::unprepared('
            CREATE TRIGGER trg_users_to_conductores
            AFTER INSERT ON users
            FOR EACH ROW
            BEGIN
                IF NEW.rol = "conductor" THEN
                    INSERT INTO conductores (
                        conduc_cc,
                        conduc_nombres,
                        conduc_estado,
                        conduc_licencia,
                        conduc_fecha,
                        conduc_cel
                    ) VALUES (
                        NEW.cedula,
                        CONCAT(NEW.nombres, " ", NEW.apellidos),
                        1,
                        NEW.cedula,
                        CURRENT_DATE(),
                        NEW.celular
                    );
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
     public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_users_to_conductor');
    }
};
