<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Matricula::with(['estudiante', 'especialidad', 'ingreso'])->orderBy('id', 'desc');

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $matriculas = $query->get();

            return response()->json(['matriculas', $matriculas], 200);

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
        /*try{
            $request->validate([
                'ingreso' => 'required|in:Nuevo Ingreso,Reingreso',
                'foto' => 'required|string|max:300',
                'estudiante_id' => 'required|exists:estudiantes,id',
                'enfermedad_id' => 'required|exists:enfermedades,id',
                'seccion_id' => 'nullable|exists:secciones,id',
                'especialidad_id' => 'required|exists:especalidades,id',
            ]);

            $anioActual = (string) now()->year;

            $matricula = Matricula::create([
                'ingreso' => $request->ingreso,
                'anio' => $anioActual,

            ]);
        }*/
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $matricula = Matricula::with(['estudiante', 'nie.estudiante'])->findOrFail($id);

            return response()->json($matricula);

        } catch (\ModelNotFoundException $e) {
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
        //
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
        try {

            $request->validate([
                'estado' => 'required|in:pendiente,aprobado,rechazado',
            ]);

            $matriculas = Matricula::with('estudiantes')->findOrFail($id);

            if (! $matriculas) {
                return response()->json([
                    'message' => 'Matricula no encontrada',
                ], 404);
            }

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

            $matriculas->estado = $nuevo;

            $matriculas->update();

            if($nuevo === 'aprobado'){
                $estudiante = $matriculas->estudiante;

                if($estudiante->estado !== 'inscrito'){
                    return response()->json([
                        'message' => 'El estudiante no esta inscrito'
                    ],404);
                }
                $estudiante->estado = 'activo';
                $estudiante->update();
            }

            return response()->json([
                'message' => "La matricula $matriculas ha sido actualizada correctamente",
                'matriculas' => $matriculas,
            ]);
        } catch (\Exception $e) {
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
            $estudiante = Estudiante::with('padres')->find($estudianteId);

            return response()->json([
                'padres' => $estudiante->padres,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los padres del estudiante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*public function validarGrado($estudiante, $grado)
    {
        try{
            $estudiante = Estudiante::with('matriculas')->findOrFail($estudiante);


        }
    }*/

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
                ->get();

            if ($secciones->isEmpty()) {
                return response()->json([
                    'disponibilidad' => false,
                    'message' => 'No hay secciones disponibles para el grado y turno especificados.',
                ], 200);

            }

            $cuposDisponibles = 0;

            foreach ($secciones as $seccion) {
                $matriculasCount = Matricula::where('seccion_id', $seccion->id)
                    ->where('anio', $anioActual)
                    ->whereIn('estado', ['pendiente', 'aprobado'])
                    ->count();
                $cuposDisponibles += max(0, $seccion->capacidad - $matriculasCount);

            }

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

            $secciones = Seccion::where('grado_id', $request->grado_id)
                ->where('turno', $request->turno)
                ->where('anio', $anioActual)
                ->get();

            $seccionesDisponibles = [];

            foreach ($secciones as $seccion) {
                $matriculasCount = Matricula::where('seccion_id', $seccion->id)
                    ->where('anio', $anioActual)
                    ->whereIn('estado', ['pendiente', 'aprobado'])
                    ->count();

                $cuposDisponibles = max(0, $seccion->capacidad - $matriculasCount);

                if ($cuposDisponibles > 0) {
                    $seccionesDisponibles[] = [
                        'id' => $seccion->id,
                        'nombre' => $seccion->nombre,
                        'capacidad' => $seccion->capacidad,
                        'cupos_disponibles' => $cuposDisponibles,
                    ];
                }
            }

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
                ->whereIn('estado', ['pendiente', 'aprobado']) // Considerar solo matrículas pendientes o aprobadas
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
