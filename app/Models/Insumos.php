<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumos extends Model
{
    use HasFactory;
    protected $table = "insumos";
    protected $primaryKey = 'id_insumo';

    protected $fillable = ['nombre_insumo', 'id_categoria', 'stock'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'proveedores_insumos', 'id_insumo', 'id_proveedor');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'productos_insumos', 'id_insumo', 'id_producto')->withPivot('cantidad_usada');
    }
}
