<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encargado extends Model
{
    protected $table = 'encargados';

    protected $fillable = [
        'nombre',
        'parentesco',
        'telefono',
        'dui',
        'direccion',
        'correo',
    ];

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }
}
