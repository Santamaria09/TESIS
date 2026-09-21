<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nota_Final extends Model
{
    protected $table = 'notas_finales';

    protected $fillable = [
        'matricula_id',
        'promedio_anual',
        'fecha_cierre',
        'estado',
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }
}
