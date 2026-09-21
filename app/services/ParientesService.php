<?php

namespace App\Services;

use App\Models\Pariente;
use App\Models\Estudiante;
use App\Models\Padre;

class ParientesService
{
    public function relacionarPadre($estudianteId, $padreId, $parentesco)
    {
        Estudiante::findOrFail($estudianteId);
        Padre::findOrFail($padreId);

        return Pariente::create([
            'estudiante_id' => $estudianteId,
            'padre_id'       => $padreId,
            'parentesco'     => $parentesco,
        ]);
    }
}
