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
        Schema::create('encargados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->enum('parentesco', ['Padre', 'Madre', 'Tío(a)', 'Abuelo(a)', 'Hermano(a)', 'Otro']);
            $table->string('telefono', 10);
            $table->string('dui', 10);
            $table->string('direccion', 100);
            $table->string('correo', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encargados');
    }
};
