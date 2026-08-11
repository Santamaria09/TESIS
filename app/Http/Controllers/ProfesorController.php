<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profesor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ProfesorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $profesores = Profesor::with('user')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'profesores' => $profesores
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los profesores',
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
                'fecha_nacimiento' => 'required|date',
                'codigo' => 'required|string|max:15|unique:profesores,codigo',
                'telefono' => 'required|string|max:10|unique:profesores,telefono|regex:/^[267][0-9]{3}-?[0-9]{4}$/',
                'direccion' => 'required|string|max:100',
                'user_id' => 'required|exists:users,id|unique:profesores,user_id',
            ], [
                'fecha_nacimiento.required' => 'La fecha de nacimiento del profesor es obligatoria.',
                'codigo.max' => 'El código del profesor no debe exceder los 15 caracteres.',
                'codigo.unique' => 'El código del profesor ya está registrado.',
                'telefono.unique' => 'El teléfono del profesor ya está registrado.',
                'direccion.required' => 'La dirección del profesor es obligatoria.',
                'user_id.unique' => 'El usuario ya está asignado a otro profesor.',
            ]);

            $profesor = Profesor::create([
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'codigo' => $request->codigo,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'user_id' => $request->user_id,
            ]);

            return response()->json([
                'message' => 'Profesor registrado exitosamente',
                'profesor' => $profesor
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el profesor',
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
            $profesor = Profesor::with('user')->findOrFail($id);

            return response()->json($profesor);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: profesor no encontrado',
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
            $profesor = Profesor::findOrFail($id);

            $request->validate([
                'fecha_nacimiento' => 'required|date',
                'codigo' => 'required|string|max:15|unique:profesores,codigo,' . $id,
                'telefono' => 'required|string|max:10|unique:profesores,telefono,' . $id . '|regex:/^[267][0-9]{3}-?[0-9]{4}$/',
                'direccion' => 'required|string|max:100',
                'user_id' => 'required|exists:users,id|unique:profesores,user_id,' . $id,
            ], [
                'fecha_nacimiento.required' => 'La fecha de nacimiento del profesor es obligatoria.',
                'codigo.max' => 'El código del profesor no debe exceder los 15 caracteres.',
                'codigo.unique' => 'El código del profesor ya está registrado.',
                'telefono.unique' => 'El teléfono del profesor ya está registrado.',
                'direccion.required' => 'La dirección del profesor es obligatoria.',
            ]);

            $profesor->update([
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'codigo' => $request->codigo,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'user_id' => $request->user_id,
            ]);

            return response()->json([
                'message' => 'Profesor actualizado exitosamente',
                'profesor' => $profesor->load('user')
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: profesor no encontrado',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el profesor',
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
            $profesor = Profesor::with('secciones', 'asignaciones')->findOrFail($id);

            if ($profesor->secciones()->exists() || $profesor->asignaciones()->exists()) {
                return response()->json([
                    'message' => 'No se puede eliminar el profesor porque tiene secciones o asignaciones asociadas.'
                ], 400);
            }

            $profesor->delete();

            return response()->json([
                'message' => 'Profesor eliminado exitosamente'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: profesor no encontrado',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el profesor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
