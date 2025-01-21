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
        Schema::table('productos_insumos', function (Blueprint $table) {
            $table->foreign(['id_producto'], 'productos_insumos_ibfk_1')->references(['id_producto'])->on('productos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_insumo'], 'productos_insumos_ibfk_2')->references(['id_insumo'])->on('insumos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_insumos', function (Blueprint $table) {
            $table->dropForeign('productos_insumos_ibfk_1');
            $table->dropForeign('productos_insumos_ibfk_2');
        });
    }
};
