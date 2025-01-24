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
        Schema::create('productos_insumos', function (Blueprint $table) {
            $table->integer('id_producto');
            $table->integer('id_insumo')->index('id_insumo');
            $table->integer('cantidad_usada')->nullable();
            $table->timestamps();
            $table->primary(['id_producto', 'id_insumo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_insumos');
    }
};
