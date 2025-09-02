<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movil extends Model
{
    protected $table = 'movil';

    protected $fillable = [
        'mo_taxi',
        'mo_conductor',
        'mo_estado'
    ];

    public $timestamps = false;
}
