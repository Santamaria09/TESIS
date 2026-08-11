<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $secciones = Seccion::with('grado', 'profesor')->orderBy('id', 'desc')->get();
            return response()->json(['secciones' => $secciones], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las secciones',
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
                'nombre' => 'required|string|max:50|',
                'capacidad' => 'required|integer',
                'turno' => 'required|in:matutino,vespertino',
                'anio' => 'required|integer|min:2025|max:2100',
                'grado_id' => 'required|exists:grados,id',
                'profesor_id' => 'required|exists:profesores,id',

            ],
            [
                'nombre.required' => 'El nombre de la sección es obligatorio.',
                'capacidad.required' => 'La capacidad de la sección es obligatoria.',
                'turno.required' => 'El turno de la sección es obligatorio.',
                'anio.required' => 'El año de la sección es obligatorio.',
                'grado_id.exists' => 'El grado seleccionado no es válido.',
                'profesor_id.exists' => 'El profesor seleccionado no es válido.',
            ]);

            $existSeccion = Seccion::where('nombre', $request->nombre)
                ->where('grado_id', $request->grado_id)
                ->exists();

            if ($existSeccion) {
                return response()->json([
                    'message' => 'Error: La sección ya está registrada para este grado.'
                ], 400);
            }

            $seccion = Seccion::create([
                'nombre' => $request->nombre,
                'capacidad' => $request->capacidad,
                'turno' => $request->turno,
                'anio' => $request->anio,
                'grado_id' => $request->grado_id,
                'profesor_id' => $request->profesor_id,
            ]);

            return response()->json(['message' => 'Sección registrada exitosamente', 'seccion' => $seccion], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la sección',
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
            $seccion = Seccion::with('grado', 'profesor')->findOrFail($id);
            return response()->json($seccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: sección no encontrada',
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
            $seccion = Seccion::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|max:50|',
                'capacidad' => 'required|integer',
                'turno' => 'required|in:matutino,vespertino',
                'anio' => 'required|integer|min:2025|max:2100',
                'grado_id' => 'required|exists:grados,id',
                'profesor_id' => 'required|exists:profesores,id',

            ],
            [
                'nombre.required' => 'El nombre de la sección es obligatorio.',
                'capacidad.required' => 'La capacidad de la sección es obligatoria.',
                'turno.required' => 'El turno de la sección es obligatorio.',
                'anio.required' => 'El año de la sección es obligatorio.',
                'grado_id.exists' => 'El grado seleccionado no es válido.',
                'profesor_id.exists' => 'El profesor seleccionado no es válido.',
            ]);

            $existSeccion = Seccion::where('nombre', $request->nombre)
                ->where('grado_id', $request->grado_id)
                ->where('id', '<>', $id)
                ->exists();

            if ($existSeccion) {
                return response()->json([
                    'message' => 'Error: La sección ya está registrada para este grado.'
                ], 400);
            }

            $seccion->update([
                'nombre' => $request->nombre,
                'capacidad' => $request->capacidad,
                'turno' => $request->turno,
                'anio' => $request->anio,
                'grado_id' => $request->grado_id,
                'profesor_id' => $request->profesor_id,
            ]);

            return response()->json(['message' => 'Sección actualizada exitosamente', 'seccion' => $seccion], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: sección no encontrada',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la sección',
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
            $seccion = Seccion::findOrFail($id);

            if ($seccion->matriculas()->exists()) {
                return response()->json([
                    'message' => 'Error: no se puede eliminar la sección porque tiene alumnos asociados.'
                ], 400);
            }

            $seccion->delete();

            return response()->json(['message' => 'Sección eliminada exitosamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: sección no encontrada',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la sección',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
