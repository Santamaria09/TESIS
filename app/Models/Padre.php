<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Padre extends Model
{
    protected $table = 'padres';

    protected $fillable = [
        'nombre',
        'dui',
        'email',
        'telefono',
    ];

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class,
         'parientes',
          'padre_id',
           'estudiante_id')->withPivot('parentesco');
    }

}
