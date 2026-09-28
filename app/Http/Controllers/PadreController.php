<?php

namespace App\Http\Controllers;

use App\Rules\Dui;
use App\Services\PadreService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Padre; // Asegúrate de importar el modelo si no usas el Service para el GET

class PadreController extends Controller
{
    protected PadreService $padreService;

    public function __construct(PadreService $padreService)
    {
        $this->padreService = $padreService;
    }
    
    public function buscarPorDui(Request $request)
    {
        $request->validate([
            'dui' => 'required|string'
        ]);

        try {
            $padre = $this->padreService->buscarPorDui($request->dui);

            if ($padre) {
                return response()->json([
                    'encontrado' => true,
                    'padre' => $padre
                ], 200);
            }

            return response()->json([
                'encontrado' => false,
                'message' => 'No se encontró ningún padre con este DUI'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar el padre',
                'error' => $e->getMessage()
            ], 500);
        }
    }    
   public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:80',
                'dui' => ['required', new Dui, 'unique:padres,dui'],
                'telefono' => 'nullable|string|max:15',
                'email' => 'nullable|email|max:100',
                'es_encargado' => 'required|boolean',
            ]);

            $validated['user_id'] = $validated['es_encargado'] ? auth()->id() : null;

            $padre = $this->padreService->crear($validated);

            return response()->json([
                'message' => 'Padre registrado exitosamente',
                'padre' => $padre,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar el padre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}