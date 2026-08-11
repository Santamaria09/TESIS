<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = [
        'anio',
        'profesor_id',
        'materia_id',
        'grado_id',

    ];

    public function profesor()
    {
        return $this->belongsTo(Profesor::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}
