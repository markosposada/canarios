<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::table('disponibles', function (Blueprint $table) {
        $table->text('dis_audio')->nullable()->after('dis_dire');
    });
}
public function down()
{
    Schema::table('disponibles', function (Blueprint $table) {
        $table->dropColumn('dis_audio');
    });
}

};
