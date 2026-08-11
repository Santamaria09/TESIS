<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->enum('genero', ['Masculino', 'Femenino']);
            $table->unsignedBigInteger('distrito_id');
            $table->foreign('distrito_id')->references('id')->on('distritos');
            $table->string('nie', 10)->unique();
            $table->string('direccion', 100);
            $table->string('canton', 50)->nullable();
            $table->enum('estado',['inscrito', 'activo', 'inactivo', 'egresado', 'graduado'])->default('inscrito');
            $table->unsignedBigInteger('registro_id');
            $table->foreign('registro_id')->references('id')->on('registros');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
