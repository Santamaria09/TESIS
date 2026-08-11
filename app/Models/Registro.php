<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registro extends Model
{
    protected $table = 'registros';

    protected $fillable = [
        'dui',
    ];

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class);
    }
}
