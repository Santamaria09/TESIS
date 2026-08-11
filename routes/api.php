<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\DiscapacidadController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\UbicacionController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->group(function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

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

    // 🟢 1. Rutas accesibles para Usuarios Registrados (CLIENTE) y Administradores
    // Permite matricular y registrar estudiantes
    Route::middleware(['role:CLIENTE|DIRECTOR'])->group(function () {
        Route::post('registrado', [RegistroController::class, 'registrado']);
        Route::apiResource('estudiantes', EstudianteController::class);
    });

    // 🔴 2. Rutas Exclusivas para el Administrador (Gestión del Sistema)
    Route::middleware(['role:DOCENTE'])->group(function () {
        Route::apiResource('materias', MateriaController::class);
        Route::apiResource('grados', GradoController::class);
        Route::apiResource('especialidades', EspecialidadController::class);
        Route::apiResource('discapacidades', DiscapacidadController::class);
        Route::apiResource('secciones', SeccionController::class);
        //Route::apiResource('profesores', ProfesorController::class);
    });



});

