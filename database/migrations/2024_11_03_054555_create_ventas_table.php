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
        Schema::create('ventas', function (Blueprint $table) {
            $table->integer('id_venta', true);
            $table->string('num_factura')->unique('num_factura');
            $table->dateTime('fecha_venta');
            $table->string('documento_cliente')->nullable();
            $table->unsignedBigInteger('id_cajero')->nullable()->index('id_cajero');
            $table->float('total_venta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
