<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class MateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $materias = Materia::orderBy('id', 'desc')->get();
            return response()->json(['materias' => $materias], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las materias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:50|unique:materias,nombre',
            ],
            [
                'nombre.required' => 'El nombre de la materia es obligatorio.',
                'nombre.string' => 'El nombre de la materia debe ser una cadena de texto.',
                'nombre.max' => 'El nombre de la materia no debe exceder los 50 caracteres.',
                'nombre.unique' => 'El nombre de la materia ya existe.',
            ]);

            $materia = Materia::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json(['message' => 'Materia registrada exitosamente', 'materia' => $materia], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la materia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $materia = Materia::findOrFail($id);
            return response()->json($materia);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error materia no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $materia = Materia::findOrFail($id);

            $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('materias', 'nombre')->ignore($id)
                ],
            ],
            [
                'nombre.unique' => 'El nombre de la materia ya existe.',
            ]);

            $materia->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json(['message' => 'Materia actualizada exitosamente', 'materia' => $materia], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error materia no encontrada',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la materia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $materia = Materia::with('asignaciones')->findOrFail($id);
            if ($materia->asignaciones()->exists()) {
                return response()->json(['message' => 'No se puede eliminar la materia porque tiene asignaciones asociadas'], 400);
            }

            $materia->delete();
            return response()->json(['message' => 'Materia eliminada exitosamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error materia no encontrada',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la materia',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
