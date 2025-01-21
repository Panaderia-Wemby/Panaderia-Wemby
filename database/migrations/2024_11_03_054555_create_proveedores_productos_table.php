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
        Schema::create('proveedores_productos', function (Blueprint $table) {
            $table->integer('id_proveedor');
            $table->integer('id_producto')->index('id_producto');
            $table->timestamps();
            $table->primary(['id_proveedor', 'id_producto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores_productos');
    }
};
