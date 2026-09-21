<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EstudianteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class EstudianteController extends Controller
{
    protected EstudianteService $estudianteService;

    public function __construct(EstudianteService $estudianteService)
    {
        $this->estudianteService = $estudianteService;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre'           => 'required|string|max:80',
                'fecha_nacimiento' => 'required|date',
                'genero'           => 'required|in:Masculino,Femenino',
                'distrito_id'      => 'required|exists:distritos,id',
                'nie'              => 'nullable|string|max:10|unique:estudiantes,nie',
                'direccion'        => 'required|string|max:100',
                'canton'           => 'nullable|string|max:50',
            ]);

            $validated['user_id'] = auth()->id();

            $estudiante = $this->estudianteService->crear($validated);

            return response()->json([
                'message'    => 'Estudiante registrado exitosamente',
                'estudiante' => $estudiante
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre'           => 'required|string|max:80',
                'fecha_nacimiento' => 'required|date',
                'genero'           => 'required|in:Masculino,Femenino',
                'distrito_id'      => 'required|exists:distritos,id',
                'nie'              => "nullable|string|max:10|unique:estudiantes,nie,{$id}",
                'direccion'        => 'required|string|max:100',
                'canton'           => 'nullable|string|max:50',
                'user_id'          => 'required|exists:users,id',
            ]);

            $estudiante = $this->estudianteService->actualizar($id, $validated);

            return response()->json([
                'message'    => 'Estudiante actualizado exitosamente',
                'estudiante' => $estudiante
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estudiante no encontrado para actualizar',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estudiante',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
