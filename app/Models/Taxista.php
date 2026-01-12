<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxista extends Model
{
    protected $table = 'taxistas';

    protected $fillable = [
        'nombre',
        'apellidos',
        'cedula',
        'celular',
        'movil',
        'placa_taxi',
        'correo_electronico',
    ];
}

