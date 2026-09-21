<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    protected $table = 'grados';
    protected $fillable = [
        'nombre',
    ];

    public function secciones()
    {
        return $this->hasMany(Seccion::class);
    }

}
