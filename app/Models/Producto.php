<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_producto';
    protected $table = "productos";
    protected $fillable = ['codigo_producto', 'nombre', 'id_categoria', 'precio', 'stock'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'proveedores_productos', 'id_producto', 'id_proveedor');
    }
    
    public function insumos()
    {
        return $this->belongsToMany(Insumos::class, 'productos_insumos', 'id_producto', 'id_insumo')->withPivot('cantidad_usada');
    }

    public function detallesVenta()
    {
        return $this->hasMany(detalle_venta::class, 'id_producto');
    }
}
