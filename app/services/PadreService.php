<?php

namespace App\Services;

use App\Models\Padre;

class PadreService
{
    public function obtener($id)
    {
        return Padre::with('user')->findOrFail($id);
    }

    public function crear(array $data): Padre
    {
        if (! empty($data['user_id'])) {
            $existe = Padre::where('user_id', $data['user_id'])->exists();

            if ($existe) {
                throw new \Exception('El usuario autenticado ya está registrado como padre.');
            }
        }

        return Padre::create([
            'nombre' => $data['nombre'],
            'dui' => $data['dui'],
            'telefono' => $data['telefono'],
            'email' => $data['email'],
            'user_id' => $data['user_id'] ?? null,
        ]);
    }

    public function actualizar($id, array $data)
    {
        $padre = Padre::findOrFail($id);

        $padre->update([
            'nombre' => $data['nombre'],
            'dui' => $data['dui'],
            'telefono' => $data['telefono'],
            'email' => $data['email'],
            'user_id' => $data['user_id'] ?? null,
        ]);

        return $padre->load('user');
    }
}
