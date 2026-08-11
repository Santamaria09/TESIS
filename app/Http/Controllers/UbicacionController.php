<?php

namespace App\Http\Controllers;

use App\Models\Distrito;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UbicacionController extends Controller
{
    public function distritos()
    {
        $distritos = Distrito::with('municipio.departamento')
            ->orderBy('nombre')
            ->get();

        return response()->json(['distritos' => $distritos], 200);
    }

    public function distrito(string $id)
    {
        try {
            $distrito = Distrito::with('municipio.departamento')->findOrFail($id);

            return response()->json(['distrito' => $distrito], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Distrito no encontrado',
            ], 404);
        }
    }
}
