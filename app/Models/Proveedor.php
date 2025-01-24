<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    protected $table = "proveedores";
    protected $primaryKey = 'id_proveedor';

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'proveedores_productos', 'id_proveedor', 'id_producto');
    }

    public function insumos()
    {
        return $this->belongsToMany(Insumos::class, 'proveedores_insumos', 'id_proveedor', 'id_insumo');
    }
}
