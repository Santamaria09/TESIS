<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    protected $table = 'secciones';

    protected $fillable = [
        'nombre',
        'capacidad',
        'turno',
        'anio',
        'grado_id',
        'profesor_id',
    ];

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function profesor()
    {
        return $this->belongsTo(Profesor::class);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }
}
