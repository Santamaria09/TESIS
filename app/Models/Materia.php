<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';

    protected $fillable = [
        'nombre',
    ];

    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class);
    }
}
