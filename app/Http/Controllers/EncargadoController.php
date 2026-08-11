<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Encargado;
use App\Rules\Dui;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class EncargadoController extends Controller
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
            $request->validate([
                'nombre' => 'required|string|max:50',
                'parentesco' => 'required|in:Padre,Madre,Tío(a),Abuelo(a),Hermano(a),Otro',
                'telefono' => 'required|string|max:10|regex:/^[267][0-9]{3}-?[0-9]{4}$/',
                'dui' => ['required', 'string', 'max:10', new Dui()],
                'direccion' => 'required|string|max:100',
                'correo' => 'nullable|email|max:100',
            ],
            [
                'nombre.required' => 'El nombre es obligatorio.',
                'parentesco.required' => 'El parentesco es obligatorio.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'dui.required' => 'El DUI es obligatorio.',
                'direccion.required' => 'La dirección es obligatoria.',
                'direccion.max' => 'La dirección no debe exceder los 100 caracteres.',
                'correo.max' => 'El correo electrónico no debe exceder los 100 caracteres.',
            ]);

            $encargado = Encargado::create([
                'nombre' => $request->nombre,
                'parentesco' =>$request->parentesco,
                'telefono' =>$request->telefono,
                'dui' =>$request->dui,
                'direccion' =>$request->direccion,
                'correo' =>$request->correo,
            ]);

            return response()->json([
                'message' => 'Encargado creado exitosamente',
                'encargado' => $encargado], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' =>'Error al registrar el encargado',
                'error' => $e->getMessage()
                ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $encargado = Encargado::with('matriculas.estudiante')->findOrFail($id);
            return response()->json($encargado);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: encargado no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $encargado = Encargado::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|max:50',
                'parentesco' => 'required|in:Padre,Madre,Tío(a),Abuelo(a),Hermano(a),Otro',
                'telefono' => 'required|string|max:10|regex:/^[267][0-9]{3}-?[0-9]{4}$/',
                'dui' => ['required', 'string', 'max:10', new Dui()],
                'direccion' => 'required|string|max:100',
                'correo' => 'nullable|email|max:100',
            ],
            [
                'nombre.required' => 'El nombre es obligatorio.',
                'parentesco.required' => 'El parentesco es obligatorio.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'dui.required' => 'El DUI es obligatorio.',
                'direccion.required' => 'La dirección es obligatoria.',
                'direccion.max' => 'La dirección no debe exceder los 100 caracteres.',
                'correo.max' => 'El correo electrónico no debe exceder los 100 caracteres.',
            ]);

            $encargado->update([
                'nombre' => $request->nombre,
                'parentesco' => $request->parentesco,
                'telefono' => $request->telefono,
                'dui' => $request->dui,
                'direccion' => $request->direccion,
                'correo' => $request->correo,
            ]);

            return response()->json([
                'message' => 'Encargado actualizado exitosamente',
                'encargado' => $encargado
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Error: encargado no encontrado',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el encargado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
