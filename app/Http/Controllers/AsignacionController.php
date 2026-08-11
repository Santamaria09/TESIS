<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asignacion;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class AsignacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $asignaciones = Asignacion::with('profesor', 'materia', 'grado')->orderBy('id', 'desc')->get();
            return response()->json(['asignaciones' => $asignaciones], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las asignaciones',
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
                'anio' => 'required|integer|digits:4|min:2025|max:2100',
                'profesor_id' => 'required|exists:profesores,id',
                'materia_id' => 'required|exists:materias,id',
                'grado_id' => 'required|exists:grados,id',

            ],
            [
                'anio.required' => 'El año es obligatorio.',
                'anio.digits' => 'El año debe tener 4 dígitos.',
                'anio.min' => 'El año no puede ser menor a 2025.',
                'profesor_id.required' => 'El profesor es obligatorio.',
                'materia_id.required' => 'La materia es obligatoria.',
                'grado_id.required' => 'El grado es obligatorio.',
            ]);

            $exits = Asignacion::where('profesor_id', $request->profesor_id)
                ->where('anio', $request->anio)
                ->where('materia_id', $request->materia_id)
                ->where('grado_id', $request->grado_id)
                ->exists();

            if ($exits) {
                return response()->json([
                    'message' => 'La asignación ya existe para el profesor, materia, grado y año especificados.'
                ], 422);
            }

            $asignacion = Asignacion::create([
                'anio' => $request->anio,
                'profesor_id' => $request->profesor_id,
                'materia_id' => $request->materia_id,
                'grado_id' => $request->grado_id,
            ]);

            return response()->json(['message' => 'Asignación registrada exitosamente', 'asignacion' => $asignacion], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la asignación',
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
            $asignacion = Asignacion::with('profesor', 'materia', 'grado')->findOrFail($id);
            return response()->json(['asignacion' => $asignacion], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: asignación no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $asignacion = Asignacion::findOrFail($id);

            // falta metodo de evaluacion

            
            $asignacion->delete();
            return response()->json(['message' => 'Asignación eliminada exitosamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: asignación no encontrada',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
