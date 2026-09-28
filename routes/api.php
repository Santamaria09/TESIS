<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\DiscapacidadController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\PadreController;
use App\Http\Controllers\UbicacionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Ruta restaurada para la validación del DUI en el registro
    Route::post('validar-dui', [AuthController::class, 'validarDui']); 

    Route::middleware('auth:api')->group(function(){
        Route::get('me',[AuthController::class, 'me']);
        Route::post('logout',[AuthController::class, 'logout']);
        Route::post('refresh',[AuthController::class, 'refresh']);
    });
});

Route::apiResource('profesores', ProfesorController::class);
Route::get('distritos', [UbicacionController::class, 'distritos']);
Route::get('distritos/{id}', [UbicacionController::class, 'distrito']);

Route::middleware(['auth:api'])->group(function () {
    // Estas rutas de consulta deben estar accesibles para el Cliente (para llenar los selects)
    Route::get('grados', [GradoController::class]);
    Route::get('especialidades', [EspecialidadController::class]);
    Route::get('discapacidades', [DiscapacidadController::class]);
});

// 🟢 1. Rutas accesibles para Usuarios Registrados (CLIENTE) y Administradores
Route::middleware(['auth:api', 'role:CLIENTE|DIRECTOR'])->group(function () {
    Route::post('padres/buscar-dui', [PadreController::class, 'buscarPorDui']);
    Route::apiResource('estudiantes', EstudianteController::class);
    Route::apiResource('padres', PadreController::class);
    Route::apiResource('matriculas', MatriculaController::class);

    // Métodos adicionales de matrículas integrados de forma segura
    Route::put('matriculas/{id}/estado', [MatriculaController::class, 'estadoMatricula']);
    Route::post('matriculas/buscar-nie', [MatriculaController::class, 'buscarNie']);
    Route::get('matriculas/estudiantes-inscritos', [MatriculaController::class, 'estudianteInscrito']);
    Route::get('matriculas/estudiantes/{estudianteId}/padres', [MatriculaController::class, 'padresEstudiante']);
    Route::post('matriculas/validar-grado/{estudiante}/{grado}', [MatriculaController::class, 'validarGrado']);
    Route::post('matriculas/disponibilidad-seccion', [MatriculaController::class, 'disponibilidadSeccion']);
    Route::post('matriculas/secciones-disponibles', [MatriculaController::class, 'seccionesDisponibles']);
    Route::post('matriculas/duplicidad', [MatriculaController::class, 'duplicidadMatricula']);
});

// 🔴 2. Rutas Exclusivas para el Administrador (Gestión del Sistema)
Route::middleware(['auth:api', 'role:DOCENTE'])->group(function () {
    Route::apiResource('materias', MateriaController::class);
    Route::apiResource('grados', GradoController::class);
    Route::apiResource('especialidades', EspecialidadController::class);
    Route::apiResource('discapacidades', DiscapacidadController::class);
    Route::apiResource('secciones', SeccionController::class);
    //Route::apiResource('profesores', ProfesorController::class);
});