<?php

namespace App\Http\Controllers;

use App\Rules\Dui;
use App\Services\PadreService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PadreController extends Controller
{
    protected PadreService $padreService;

    public function __construct(PadreService $padreService)
    {
        $this->padreService = $padreService;
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

            $validated['user_id'] = $validated['es_encargado']
                ? auth()->id()
                : null;

            $padre = $this->padreService->crear($validated);

            return response()->json([
                'message' => 'Padre registrado exitosamente',
                'padre' => $padre,
            ], 201);

        } catch (ValidationException $e) {
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
