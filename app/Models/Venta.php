<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;
    protected $table = "ventas";
    protected $fillable = [
        'num_factura',
        'fecha_venta',
        'documento_cliente',
        'id_cajero',
        'total_venta'
    ];

    protected $primaryKey = "id_venta";

    public function cajero()
    {
        return $this->belongsTo(User::class, 'id_cajero');
    }

    public function detalleVenta()
    {
        return $this->hasMany(Detalle_Venta::class, 'num_factura', 'num_factura');
    }
}
