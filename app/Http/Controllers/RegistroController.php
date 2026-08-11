<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registro;
use App\Rules\Dui;

class RegistroController extends Controller
{

    public function registrado(Request $request)
    {
        try {
            $request->validate([
                'dui' => [
                'required',
                'string',
                'max:10',
                new Dui(),
                ],
            ],
            [
                'dui.required' => 'El DUI es obligatorio.',
            ]);

            $registro = Registro::where('dui', $request->dui)->first();

            if (!$registro) {
                $registro = Registro::create([
                    'dui' => $request->dui,
                ]);

                return response()->json([
                    'message' => 'Registro exitoso.',
                    'registro' => $registro,
                    'esNuevo' => true
                ], 201);
            }

            // Si ya existe, permitir el acceso
            return response()->json([
                'message' => 'Bienvenido.',
                'registro' => $registro,
                'esNuevo' => false
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    
}
