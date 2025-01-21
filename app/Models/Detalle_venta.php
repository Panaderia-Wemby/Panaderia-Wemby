<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detalle_venta extends Model
{
    use HasFactory;
    protected $table = "detalle_venta";

    protected $fillable = [
        'num_factura',
        'id_producto',
        'cantidad',
        'precio_unitario'
    ];

    protected $primaryKey = "id_detalle";

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'num_factura', 'num_factura');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
