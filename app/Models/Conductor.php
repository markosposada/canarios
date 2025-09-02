<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    protected $table = 'conductores'; // si tu tabla no es "conductors"
    protected $primaryKey = 'conduc_cc';
    public $timestamps = false;

    protected $fillable = [
        'conduc_cc',
        'conduc_nombres',
        'conduc_licencia',
        'conduc_fecha',
        'conduc_estado',
    ];
}
