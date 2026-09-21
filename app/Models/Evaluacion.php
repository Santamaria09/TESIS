<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evalucaiones';

    protected $fillable = [
        'nombre',
        'porcentaje',
        'perido_escolar_id',
        'asignacion_id',
    ];

    public function asignacion()
    {
        return $this->belongTo(Asignacion::class, 'asignacion_id');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoEscolar::class, 'periodo_escolar_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }
}
