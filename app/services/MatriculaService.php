<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MatriculaService
{
    public function listar($estado = null)
    {
        $query = Matricula::with([
            'estudiante:id,nombre',
            'especialidad:id,nombre',
        ])
            ->select(
                'id',
                'created_at',
                'ingreso',
                'estado',
                'estudiante_id',
                'especialidad_id'
            )
            ->orderBy('id', 'desc');

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $query->get();
    }

    public function crear(array $datos, $foto = null, $documento = null)
    {
        DB::beginTransaction();

        try {
            $rutaF = null;
            $rutaDocumento = null;
            $nombre = null;
            $nombreDocumento = null;

            if ($foto) {
                $nombre = 'foto_'.uniqid().'.'.$foto->getClientOriginalExtension();

                $foto->storeAs('public/foto', $nombre);

                $rutaF = '/storage/foto/'.$nombre;
            }

            if ($documento) {
                $nombreDocumento = 'documento_'.uniqid().'.'.$documento->getClientOriginalExtension();

                $documento->storeAs('public/documentos', $nombreDocumento);

                $rutaDocumento = '/storage/documentos/'.$nombreDocumento;
            }

            $matricula = Matricula::create([
                'ingreso' => $datos['ingreso'],
                'anio' => (string) now()->year,
                'estado' => 'pendiente',
                'foto' => $rutaF,
                'documento' => $rutaDocumento,
                'es_trasladado' => $datos['es_trasladado'],
                'estudiante_id' => $datos['estudiante_id'],
                'enfermedad_id' => $datos['enfermedad_id'],
                'seccion_id' => $datos['seccion_id'] ?? null,
                'especialidad_id' => $datos['especialidad_id'],
                'encargado_id' => $datos['encargado_id'] ?? null,
            ]);

            DB::commit();

            return $matricula;

        } catch (\Exception $e) {
            DB::rollBack();

            if ($nombre && Storage::exists('public/foto/'.$nombre)) {
                Storage::delete('public/foto/'.$nombre);
            }

            if ($nombreDocumento && Storage::exists('public/documentos/'.$nombreDocumento)) {
                Storage::delete('public/documentos/'.$nombreDocumento);
            }

            throw $e;
        }
    }

    public function obtener($id)
    {
        return Matricula::with([
            'estudiante',
            'especialidad',
            'seccion',
        ])->findOrFail($id);
    }

    public function actualizar($id, array $datos)
    {
        DB::beginTransaction();

        try {
            $matricula = Matricula::findOrFail($id);

            $matricula->update([
                'seccion_id' => $datos['seccion_id'] ?? null,
                'enfermedad_id' => $datos['enfermedad_id'],
                'encargado_id' => $datos['encargado_id'],
                'especialidad_id' => $datos['especialidad_id'],
            ]);

            DB::commit();

            return $matricula;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function actualizarEstado($id, $nuevo)
    {
        DB::beginTransaction();

        try {
            $matricula = Matricula::with('estudiante')->findOrFail($id);

            $estadoActual = $matricula->estado;

            $estadosValidos = [
                'pendiente' => ['aprobado', 'rechazado'],
                'aprobado' => [],
                'rechazado' => [],
            ];

            if (! isset($estadosValidos[$estadoActual]) ||
                ! in_array($nuevo, $estadosValidos[$estadoActual])) {
                return [
                    'error' => true,
                    'status' => 400,
                    'message' => "No se puede cambiar el $estadoActual a $nuevo de la matricula",
                ];
            }

            if ($nuevo === 'aprobado') {

                if (! $matricula->seccion_id) {
                    DB::rollBack();

                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => 'No se puede aprobar la matrícula porque no tiene una sección asignada',
                    ];
                }

                $estudiante = $matricula->estudiante;

                if ($estudiante->estado !== 'inscrito') {
                    DB::rollBack();

                    return [
                        'error' => true,
                        'status' => 404,
                        'message' => 'El estudiante no esta inscrito',
                    ];
                }

                $estudiante->estado = 'activo';
                $estudiante->update();
            }

            $matricula->estado = $nuevo;
            $matricula->update();

            DB::commit();

            return [
                'error' => false,
                'matricula' => $matricula,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function buscarPorNie($nie)
    {
        $estudiante = Estudiante::with([
            'matriculas.especialidad',
        ])
            ->where('nie', $nie)
            ->first();

        if (! $estudiante) {
            return null;
        }

        $ultimaMatricula = $estudiante->matriculas
            ->sortByDesc('anio')
            ->first();

        return [
            'id' => $estudiante->id,
            'nombre' => $estudiante->nombre,
            'nie' => $estudiante->nie,
            'especialidad' => $ultimaMatricula?->especialidad?->nombre,
        ];
    }

    public function obtenerEstudiantesInscritos()
    {
        return Estudiante::select('id', 'nombre')
            ->where('estado', 'inscrito')
            ->get();
    }

    public function obtenerPadresEstudiante($estudianteId)
    {
        $estudiante = Estudiante::with('padres')
            ->findOrFail($estudianteId);

        return $estudiante->padres;
    }

    public function validarGrado($estudianteId, $grado, $ingreso)
    {
        if ($ingreso === 'Nuevo ingreso') {
            return [
                'permitido' => true,
                'message' => 'El estudiante es de nuevo ingreso. No requiere validar historial.',
            ];
        }

        $estudiante = Estudiante::with([
            'matriculas.seccion',
            'matriculas.notaAnual',
        ])->findOrFail($estudianteId);

        if ($estudiante->matriculas->isEmpty()) {
            return [
                'permitido' => true,
                'message' => 'El estudiante no tiene historial de matrículas.',
            ];
        }

        $ultimaMatricula = $estudiante->matriculas
            ->sortByDesc('id')
            ->first();

        if (! $ultimaMatricula->seccion) {
            return [
                'permitido' => false,
                'status' => 422,
                'message' => 'La matrícula anterior no tiene una sección asignada.',
            ];
        }

        $ultimoGrado = $ultimaMatricula->seccion->grado_id;

        $notaAnual = $ultimaMatricula->notaAnual;

        if (! $notaAnual) {
            return [
                'permitido' => false,
                'status' => 422,
                'message' => 'El estudiante no tiene notas anuales registradas para su matrícula anterior.',
            ];
        }

        if ($notaAnual->estado !== 'aprobada') {
            return [
                'permitido' => false,
                'status' => 422,
                'message' => 'El estudiante no aprobó el año anterior. No puede matricular el grado siguiente.',
            ];
        }

        if ($notaAnual->promedio < 7.0) {
            return [
                'permitido' => false,
                'status' => 422,
                'message' => 'El estudiante no alcanzó el promedio mínimo requerido para aprobar.',
            ];
        }

        $gradoPermitido = $ultimoGrado + 1;

        if ($grado != $gradoPermitido) {
            return [
                'permitido' => false,
                'grado_permitido' => $gradoPermitido,
                'status' => 422,
                'message' => 'El estudiante aprobó el grado anterior. Solo puede matricular el grado consecutivo.',
            ];
        }

        return [
            'permitido' => true,
            'message' => 'El estudiante aprobó el año anterior y puede matricular el grado siguiente.',
        ];
    }

    public function verificarDisponibilidadSeccion($gradoId, $turno)
    {
        $anioActual = date('Y');

        $secciones = Seccion::where('grado_id', $gradoId)
            ->where('turno', $turno)
            ->where('anio', $anioActual)
            ->withCount([
                'matriculas' => function ($query) use ($anioActual) {
                    $query->where('anio', $anioActual)
                        ->whereIn('estado', ['pendiente', 'aprobado']);
                },
            ])
            ->get();

        if ($secciones->isEmpty()) {
            return [
                'disponibilidad' => false,
                'message' => 'No hay secciones disponibles para el grado y turno especificados.',
            ];
        }

        $cuposDisponibles = $secciones->sum(function ($seccion) {
            return max(
                0,
                $seccion->capacidad - $seccion->matriculas_count
            );
        });

        if ($cuposDisponibles <= 0) {
            return [
                'disponibilidad' => false,
                'message' => 'No hay cupos disponibles en las secciones para el grado y turno especificados.',
            ];
        }

        return [
            'disponibilidad' => true,
            'cupos_disponibles' => $cuposDisponibles,
        ];
    }

    public function obtenerSeccionesDisponibles($gradoId, $turno)
    {
        $anioActual = date('Y');

        $secciones = Seccion::where('grado_id', $gradoId)
            ->where('turno', $turno)
            ->where('anio', $anioActual)
            ->withCount([
                'matriculas' => function ($query) use ($anioActual) {
                    $query->where('anio', $anioActual)
                        ->whereIn('estado', ['pendiente', 'aprobado']);
                },
            ])
            ->get();

        return $secciones
            ->map(function ($seccion) {
                $cuposDisponibles = max(
                    0,
                    $seccion->capacidad - $seccion->matriculas_count
                );

                return [
                    'id' => $seccion->id,
                    'nombre' => $seccion->nombre,
                    'capacidad' => $seccion->capacidad,
                    'cupos_disponibles' => $cuposDisponibles,
                ];
            })
            ->filter(fn ($seccion) => $seccion['cupos_disponibles'] > 0)
            ->values();
    }

    public function verificarDuplicidad($estudianteId, $anio)
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->where('anio', $anio)
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->exists();
    }
}
