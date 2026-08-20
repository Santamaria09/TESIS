<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Matricula::with([
                'estudiante:id,nombre',
                'especialidad:id,nombre',
            ])
                ->select('id', 'created_at', 'ingreso', 'estado', 'estudiante_id', 'especialidad_id')
                ->orderBy('id', 'desc');

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $matriculas = $query->get();

            return response()->json(['matriculas' => $matriculas], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las matriculas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'ingreso' => 'required|in:Nuevo ingreso,Reingreso',
            'estudiante_id' => 'required|exists:estudiantes,id',
            'enfermedad_id' => 'required|exists:enfermedades,id',
            'seccion_id' => 'nullable|exists:secciones,id',
            'especialidad_id' => 'required|exists:especialidades,id',
            'encargado_id' => 'required|exists:encargados,id',
            'user_id' => 'nullable|exists:users,id',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'foto_academica' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $rutaF = null;
            $rutaAcademica = null;

            if ($request->hasFile('foto')) {
                $nombre = 'foto_'.uniqid().'.'.$request->file('foto')
                    ->getClientOriginalExtension();
                $request->file('foto')->storeAs('public/foto', $nombre);
                $rutaF = '/storage/foto/'.$nombre;
            }

            if ($request->hasFile('foto_academica')) {
            $nombreAcademica = 'foto_academica_'.uniqid().'.'.$request->file('foto_academica')->getClientOriginalExtension();
            $request->file('foto_academica')->storeAs('public/fotos_academicas', $nombreAcademica);
            $rutaAcademica = '/storage/fotos_academicas/'.$nombreAcademica;
        }

            $matricula = Matricula::create([
                'ingreso' => $request->ingreso,
                'anio' => (string) now()->year,
                'foto' => $rutaF,
                'foto_academica' => $rutaAcademica,
                'estudiante_id' => $request->estudiante_id,
                'enfermedad_id' => $request->enfermedad_id,
                'seccion_id' => $request->seccion_id,
                'especialidad_id' => $request->especialidad_id,
                'encargado_id' => $request->encargado_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Matricula creada exitosamente',
                'matricula' => $matricula,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($nombre) && Storage::exists('public/foto/'.$nombre)) {
                Storage::delete('public/foto/'.$nombre);
            }

            return response()->json([
                'message' => 'Error al crear la matricula',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $matricula = Matricula::with(['estudiante', 'especialidad', 'seccion'])->findOrFail($id);

            return response()->json($matricula);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Matricula no encontrada',
                'error' => $e->getMessage(),
            ], 404);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {

            $matricula = Matricula::findOrFail($id);

            $validated = $request->validate([
                'seccion_id' => 'nullable|exists:secciones,id',
                'enfermedad_id' => 'required|exists:enfermedades,id',
                'encargado_id' => 'required|exists:encargados,id',
                'especialidad_id' => 'required|exists:especialidades,id',
            ]);

            $matricula->seccion_id = $request->seccion_id;
            $matricula->enfermedad_id = $request->enfermedad_id;
            $matricula->encargado_id = $request->encargado_id;
            $matricula->especialidad_id = $request->especialidad_id;

            $matricula->save();

            DB::commit();

            return response()->json([
                'message' => 'Matrícula actualizada exitosamente',
                'matricula' => $matricula,
            ], 200);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'No se encontró la matrícula con el ID: '.$id,
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar la matrícula',
                'error' => $e->getMessage(),
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

    public function estadoMatricula(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $matriculas = Matricula::with('estudiante')->findOrFail($id);

            $request->validate([
                'estado' => 'required|in:pendiente,aprobado,rechazado',
            ]);

            $nuevo = $request->estado;
            $estadoA = $matriculas->estado;

            $estadosValidos = [
                'pendiente' => ['aprobado', 'rechazado'],
                'aprobado' => [],
                'rechazado' => [],
            ];

            if (! in_array($nuevo, $estadosValidos[$estadoA])) {
                return response()->json([
                    'message' => "No se puede cambiar el $estadoA a $nuevo de la matricula",
                ], 400);
            }

            if ($nuevo === 'aprobado') {

                if (! $matriculas->seccion_id) {

                    DB::rollBack();

                    return response()->json([
                        'message' => 'No se puede aprobar la matrícula porque no tiene una sección asignada',
                    ], 422);
                }
                $estudiante = $matriculas->estudiante;

                if ($estudiante->estado !== 'inscrito') {

                    DB::rollBack();

                    return response()->json([
                        'message' => 'El estudiante no esta inscrito',
                    ], 404);
                }
                $estudiante->estado = 'activo';
                $estudiante->update();
            }

            $matriculas->estado = $nuevo;

            $matriculas->update();

            DB::commit();

            return response()->json([
                'message' => 'La matricula ha sido actualizada correctamente',
                'matriculas' => $matriculas,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el estado la matricula',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function buscarNie(Request $request)
    {
        try {
            $request->validate([
                'nie' => 'required|string|max:20',
            ]);

            $estudiante = Estudiante::with([
                'matriculas.especialidad',
            ])
                ->where('nie', $request->nie)
                ->first();

            if (! $estudiante) {
                return response()->json([
                    'message' => 'El estudiante no pertenece a la institución.',
                ], 404);
            }

            $ultimaMatricula = $estudiante->matriculas->sortByDesc('anio')->first();

            return response()->json([
                'estudiante' => [
                    'id' => $estudiante->id,
                    'nombre' => $estudiante->nombre,
                    'nie' => $estudiante->nie,
                    'especialidad' => $ultimaMatricula?->especialidad?->nombre,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar el estudiante por NIE',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function estudianteInscrito(Request $request)
    {
        try {
            $estudiante = Estudiante::select('id', 'nombre')->where('estado', 'inscrito')->get();

            return response()->json([
                'estudiantes' => $estudiante,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los estudiantes inscritos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function padresEstudiante(string $estudianteId)
    {
        try {
            $estudiante = Estudiante::with('padres')->findOrFail($estudianteId);

            return response()->json([
                'padres' => $estudiante->padres,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los padres del estudiante',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function validarGrado(Request $request, $estudiante, $grado)
    {
        try {
            $request->validate([
            'ingreso' => 'required|in:Nuevo ingreso,Reingreso',
        ]);

            if ($request->ingreso === 'Nuevo ingreso') {
            return response()->json([
                'permitido' => true,
                'message' => 'El estudiante es de nuevo ingreso. No requiere validar historial.'
            ], 200);
        }

            $estudiante = Estudiante::with('matriculas.seccion',
                'matriculas.anioCursado')->findOrFail($estudiante);

            if ($estudiante->matriculas->isEmpty()) {
                return response()->json([
                    'permitido' => true,
                    'message' => 'El estudiante es de nuevo ingreso, no tiene historial',
                ]);
            }

            $ultimaMatricula = $estudiante->matriculas
                ->sortByDesc('id')->first();

            if (! $ultimaMatricula->seccion) {
                return response()->json([
                    'permitido' => false,
                    'message' => 'La matrícula anterior no tiene una sección asignada.',
                ], 422);
            }

                $ultimoCursado = $ultimaMatricula->seccion->grado_id;

                if ($ultimaMatricula->anioCursado->isEmpty()) {
                    return response()->json([
                        'permitido' => false,
                        'message' => 'No hay registros de notas para el año cursado.',
                    ], 422);
                }

                $promedioFinal = $ultimaMatricula->anioCursado->avg('nota') ?? 0;
                $aprobado = $promedioFinal >= 7.0;

                if (! $aprobado) {
                    if ($grado != $ultimoCursado) {
                        return response()->json([
                            'permitido' => false,
                            'grado_permitido' => $ultimoCursado,
                            'message' => 'El estudiante reprobó el ciclo anterior. Debe volver a cursar el mismo grado.',
                        ], 422);
                    }
                }

                if ($aprobado) {
                    $gradoSolicitado = $ultimoCursado + 1;

                    if ($grado != $gradoSolicitado) {
                        return response()->json([
                            'permitido' => false,
                            'grado_permitido' => $gradoSolicitado,
                            'message' => 'El estudiante aprobó el grado anterior. Solo se le permite matricular el grado consecutivo.',
                        ], 422);
                    }
                }

                return response()->json([
                    'permitido' => true,
                    'message' => 'Grado validado correctamente.',
                ], 200);


        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estudiante no encontrado'], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al validar el grado',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    public function disponibilidadSeccion(Request $request)
    {
        try {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'turno' => 'required|in:matutino,vespertino',
            ]);

            $anioActual = date('Y');

            $secciones = Seccion::where('grado_id', $request->grado_id)
                ->where('turno', $request->turno)
                ->where('anio', $anioActual)
                ->withCount(['matriculas' => function ($query) use ($anioActual) {
                    $query->where('anio', $anioActual)
                          ->whereIn('estado', ['pendiente', 'aprobado']);
                }])
                ->get();

            if ($secciones->isEmpty()) {
                return response()->json([
                    'disponibilidad' => false,
                    'message' => 'No hay secciones disponibles para el grado y turno especificados.',
                ], 200);
            }

            $cuposDisponibles = $secciones->sum(function ($seccion) {
                return max(0, $seccion->capacidad - $seccion->matriculas_count);
            });

            if ($cuposDisponibles <= 0) {
                return response()->json([
                    'disponibilidad' => false,
                    'message' => 'No hay cupos disponibles en las secciones para el grado y turno especificados.',
                ], 200);
            }

            return response()->json([
                'disponibilidad' => true,
                'cupos_disponibles' => $cuposDisponibles,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar la disponibilidad de secciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function seccionesDisponibles(Request $request)
    {
        try {
            $request->validate([
                'grado_id' => 'required|exists:grados,id',
                'turno' => 'required|in:matutino,vespertino',
            ]);

            $anioActual = date('Y');

            // OPTIMIZACIÓN: Contado directo con withCount
            $secciones = Seccion::where('grado_id', $request->grado_id)
                ->where('turno', $request->turno)
                ->where('anio', $anioActual)
                ->withCount(['matriculas' => function ($query) use ($anioActual) {
                    $query->where('anio', $anioActual)
                          ->whereIn('estado', ['pendiente', 'aprobado']);
                }])
                ->get();

            $seccionesDisponibles = $secciones->map(function ($seccion) {
                $cuposDisponibles = max(0, $seccion->capacidad - $seccion->matriculas_count);

                return [
                    'id' => $seccion->id,
                    'nombre' => $seccion->nombre,
                    'capacidad' => $seccion->capacidad,
                    'cupos_disponibles' => $cuposDisponibles,
                ];
            })->filter(fn($seccion) => $seccion['cupos_disponibles'] > 0)->values();

            return response()->json([
                'secciones' => $seccionesDisponibles,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las secciones disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function duplicidadMatricula(Request $request)
    {
        try {
            $request->validate([
                'estudiante_id' => 'required|exists:estudiantes,id',
                'anio' => 'required|string|size:4',
            ]);

            $anioActual = $request->anio;

            $matriculaExistente = Matricula::where('estudiante_id', $request->estudiante_id)
                ->where('anio', $anioActual)
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->exists();

            if ($matriculaExistente) {
                return response()->json([
                    'duplicidad' => true,
                    'message' => 'El estudiante ya tiene una matrícula registrada',
                ], 422);
            }

            return response()->json([
                'duplicidad' => false,
                'message' => 'El estudiante no tiene una matrícula registrada',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar la matrícula',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
