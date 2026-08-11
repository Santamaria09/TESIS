<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validated=$request->validate([
                'nombre' => 'required|string|max:80',
                'genero' => 'required|in:Masculino,Femenino',
                'distrito_id' => 'required|exists:distritos,id',
                'nie' => 'required|string|max:10|unique:estudiantes,nie',
                'direccion' => 'required|string|max:100',
                'canton' => 'nullable|string|max:50',
                'registro_id' => 'required|exists:registros,id',
            ]);


            $estudiante = Estudiante::create($validated);

            return response()->json([
                'message' => 'Estudiante registrado exitosamente',
                 'estudiante' => $estudiante], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar el estudiante',
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
            $estudiante = Estudiante::with(['padres', 'distrito'])->findOrFail($id);
        return response()->json($estudiante);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estudiante no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el estudiante',
                'error' => $e->getMessage()
            ], 500);
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
        //
    }
}
