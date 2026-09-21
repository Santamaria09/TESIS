<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grado;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class GradoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $grados = Grado::orderBy('id', 'desc')->get();
            return response()->json(['grados' => $grados], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los grados',
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
                'nombre' => 'required|string|max:50|unique:grados,nombre',
            ],
            [
                'nombre.required' => 'El nombre del grado es obligatorio.',
                'nombre.string' => 'El nombre del grado debe ser una cadena de texto.',
                'nombre.unique' => 'El nombre del grado ya está registrado.',
            ]);

            $grado = Grado::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json(['message' => 'Grado registrado exitosamente', 'grado' => $grado], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el grado',
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
            $grado = Grado::findOrFail($id);
            return response()->json(['grado' => $grado], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: grado no encontrado',
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
            $grado = Grado::findOrFail($id);

            $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('grados', 'nombre')->ignore($id)
                ],
            ],
            [
                'nombre.required' => 'El nombre del grado es obligatorio.',
                'nombre.string' => 'El nombre del grado debe ser una cadena de texto.',
                'nombre.max' => 'El nombre del grado no debe exceder los 50 caracteres.',
                'nombre.unique' => 'El nombre del grado ya existe.',
            ]);

            $grado->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json(['message' => 'Grado actualizado exitosamente', 'grado' => $grado], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: grado no encontrado',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el grado',
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
            $grado = Grado::findOrFail($id);

            if ($grado->secciones()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar el grado porque tiene secciones o asignaciones asociadas'
                ], 409);
            }

            $grado->delete();
            return response()->json(['message' => 'Grado eliminado exitosamente'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: grado no encontrado',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el grado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
