<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Detalle_venta;
use App\Models\Insumo;
use App\Models\Insumos;
use App\Models\Producto;
use App\Models\Productos_insumo;
use App\Models\Proveedores_insumo;
use App\Models\Proveedor;
use App\Models\Proveedores_producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $rol1 = new Rol();
        $rol1->nombre_rol = "Cajero";
        $rol1->save();

        $rol2 = new Rol();
        $rol2->nombre_rol = "Panadero";
        $rol2->save();

        $rol3 = new Rol();
        $rol3->nombre_rol = "Dueño";
        $rol3->save();

        $usuario = new User();
        $usuario->name = "Admin";
        $usuario->email = 'admin@gmail.com';
        $usuario->password = Hash::make("12345678");
        $usuario->rol = 3;
        $usuario->save();

        $usuario = new User();
        $usuario->name = "Pepe";
        $usuario->email = 'Pepeelgrillo@gmail.com';
        $usuario->password = Hash::make("12345678");
        $usuario->rol = 2;
        $usuario->save();

        $usuario = new User();
        $usuario->name = "Janziska";
        $usuario->email = 'peroquecojones@gmail.com';
        $usuario->password = Hash::make("12345678");
        $usuario->rol = 1;
        $usuario->save();

        $categoria = new Categoria();
        $categoria->nombre_categoria = "Panadería";
        $categoria->save();

        $categoria2 = new Categoria();
        $categoria2->nombre_categoria = "Repostería";
        $categoria2->save();

        $categoria3 = new Categoria();
        $categoria3->nombre_categoria = "Lácteos";
        $categoria3->save();

        $proveedores = new Proveedor();
        $proveedores->nombre = "Proveedor Panes";
        $proveedores->contacto = "contacto@panes.com";
        $proveedores->save();

        $proveedores2 = new Proveedor();
        $proveedores2->nombre = "Proveedor Dulces";
        $proveedores2->contacto = "contacto@dulces.com";
        $proveedores2->save();

        $proveedores3 = new Proveedor();
        $proveedores3->nombre = "Proveedor Leche";
        $proveedores3->contacto = "contacto@leche.com";
        $proveedores3->save();

        $producto1 = new Producto();
        $producto1->codigo_producto = 'PAN001';
        $producto1->nombre = 'Pan Francés';
        $producto1->id_categoria = 1;
        $producto1->precio = 1.50;
        $producto1->stock = 100;
        $producto1->save();

        $producto2 = new Producto();
        $producto2->codigo_producto = 'DUL001';
        $producto2->nombre = 'Pastel de Chocolate';
        $producto2->id_categoria = 2;
        $producto2->precio = 3.00;
        $producto2->stock = 50;
        $producto2->save();

        $insumo1 = new Insumos();
        $insumo1->nombre_insumo = 'Harina';
        $insumo1->id_categoria = 1;
        $insumo1->stock = 1000;
        $insumo1->save();

        $insumo2 = new Insumos();
        $insumo2->nombre_insumo = 'Chocolate';
        $insumo2->id_categoria = 2;
        $insumo2->stock = 500;
        $insumo2->save();

        $insumo3 = new Insumos();
        $insumo3->nombre_insumo = 'Leche';
        $insumo3->id_categoria = 3;
        $insumo3->stock = 800;
        $insumo3->save();

        $proveedorInsumo1 = new Proveedores_insumo();
        $proveedorInsumo1->id_proveedor = 1;
        $proveedorInsumo1->id_insumo = 1;
        $proveedorInsumo1->save();

        $proveedorInsumo2 = new Proveedores_insumo();
        $proveedorInsumo2->id_proveedor = 2;
        $proveedorInsumo2->id_insumo = 2;
        $proveedorInsumo2->save();

        $proveedorInsumo3 = new Proveedores_insumo();
        $proveedorInsumo3->id_proveedor = 3;
        $proveedorInsumo3->id_insumo = 3;
        $proveedorInsumo3->save();

        $proveedorProducto1 = new Proveedores_producto();
        $proveedorProducto1->id_proveedor = 1;
        $proveedorProducto1->id_producto = 1;
        $proveedorProducto1->save();

        $proveedorProducto2 = new Proveedores_producto();
        $proveedorProducto2->id_proveedor = 2;
        $proveedorProducto2->id_producto = 2;
        $proveedorProducto2->save();

        $productoInsumo1 = new Productos_insumo();
        $productoInsumo1->id_producto = 1;
        $productoInsumo1->id_insumo = 1;
        $productoInsumo1->cantidad_usada = 2;
        $productoInsumo1->save();

        $productoInsumo2 = new Productos_insumo();
        $productoInsumo2->id_producto = 2;
        $productoInsumo2->id_insumo = 2;
        $productoInsumo2->cantidad_usada = 1;
        $productoInsumo2->save();

        $productoInsumo3 = new Productos_insumo();
        $productoInsumo3->id_producto = 2;
        $productoInsumo3->id_insumo = 3;
        $productoInsumo3->cantidad_usada = 1;
        $productoInsumo3->save();

        $venta1 = new Venta();
        $venta1->num_factura = 'FAC001';
        $venta1->fecha_venta = '2024-11-04 10:00:00';
        $venta1->documento_cliente = '123456789';
        $venta1->id_cajero = 1;
        $venta1->total_venta = 50.00;
        $venta1->save();

        $venta2 = new Venta();
        $venta2->num_factura = 'FAC002';
        $venta2->fecha_venta = '2024-10-02 11:30:00';
        $venta2->documento_cliente = '987654321';
        $venta2->id_cajero = 1;
        $venta2->total_venta = 75.50;
        $venta2->save();

        $venta3 = new Venta();
        $venta3->num_factura = 'FAC003';
        $venta3->fecha_venta = '2024-10-02 14:45:00';
        $venta3->documento_cliente = '123456789';
        $venta3->id_cajero = 1;
        $venta3->total_venta = 120.00;
        $venta3->save();

        $venta4 = new Venta();
        $venta4->num_factura = 'FAC004';
        $venta4->fecha_venta = '2024-10-03 09:15:00';
        $venta4->documento_cliente = '123456789';
        $venta4->id_cajero = 1;
        $venta4->total_venta = 30.00;
        $venta4->save();

        $venta5 = new Venta();
        $venta5->num_factura = 'FAC005';
        $venta5->fecha_venta = '2024-10-05 13:00:00';
        $venta5->documento_cliente = '111222333';
        $venta5->id_cajero = 1;
        $venta5->total_venta = 200.00;
        $venta5->save();

        $venta6 = new Venta();
        $venta6->num_factura = 'FAC006';
        $venta6->fecha_venta = '2024-10-06 15:30:00';
        $venta6->documento_cliente = '444555666';
        $venta6->id_cajero = 1;
        $venta6->total_venta = 95.00;
        $venta6->save();

        $venta7 = new Venta();
        $venta7->num_factura = 'FAC007';
        $venta7->fecha_venta = '2024-10-08 17:00:00';
        $venta7->documento_cliente = '777888999';
        $venta7->id_cajero = 1;
        $venta7->total_venta = 60.00;
        $venta7->save();

        $venta8 = new Venta();
        $venta8->num_factura = 'FAC008';
        $venta8->fecha_venta = '2024-10-10 10:30:00';
        $venta8->documento_cliente = '123456789';
        $venta8->id_cajero = 1;
        $venta8->total_venta = 110.50;
        $venta8->save();

        $venta9 = new Venta();
        $venta9->num_factura = 'FAC009';
        $venta9->fecha_venta = '2024-10-12 12:00:00';
        $venta9->documento_cliente = '000111222';
        $venta9->id_cajero = 1;
        $venta9->total_venta = 150.00;
        $venta9->save();

        $venta10 = new Venta();
        $venta10->num_factura = 'FAC010';
        $venta10->fecha_venta = '2024-10-15 08:00:00';
        $venta10->documento_cliente = '333444555';
        $venta10->id_cajero = 1;
        $venta10->total_venta = 80.00;
        $venta10->save();

        $detalle_venta1 = new Detalle_venta();
        $detalle_venta1->num_factura = 'FAC001';
        $detalle_venta1->id_producto = 1;
        $detalle_venta1->cantidad = 1;
        $detalle_venta1->precio_unitario = 1.5;
        $detalle_venta1->save();

        $detalle_venta2 = new Detalle_venta();
        $detalle_venta2->num_factura = 'FAC002';
        $detalle_venta2->id_producto = 2;
        $detalle_venta2->cantidad = 1;
        $detalle_venta2->precio_unitario = 3;
        $detalle_venta2->save();

        $detalle_venta3 = new Detalle_venta();
        $detalle_venta3->num_factura = 'FAC003';
        $detalle_venta3->id_producto = 1;
        $detalle_venta3->cantidad = 1;
        $detalle_venta3->precio_unitario = 1.5;
        $detalle_venta3->save();

        $detalle_venta4 = new Detalle_venta();
        $detalle_venta4->num_factura = 'FAC004';
        $detalle_venta4->id_producto = 2;
        $detalle_venta4->cantidad = 1;
        $detalle_venta4->precio_unitario = 3;
        $detalle_venta4->save();

        $detalle_venta5 = new Detalle_venta();
        $detalle_venta5->num_factura = 'FAC005';
        $detalle_venta5->id_producto = 1;
        $detalle_venta5->cantidad = 1;
        $detalle_venta5->precio_unitario = 1.5;
        $detalle_venta5->save();

        $detalle_venta6 = new Detalle_venta();
        $detalle_venta6->num_factura = 'FAC006';
        $detalle_venta6->id_producto = 2;
        $detalle_venta6->cantidad = 1;
        $detalle_venta6->precio_unitario = 3;
        $detalle_venta6->save();

        $detalle_venta7 = new Detalle_venta();
        $detalle_venta7->num_factura = 'FAC007';
        $detalle_venta7->id_producto = 1;
        $detalle_venta7->cantidad = 1;
        $detalle_venta7->precio_unitario = 1.5;
        $detalle_venta7->save();

        $detalle_venta8 = new Detalle_venta();
        $detalle_venta8->num_factura = 'FAC008';
        $detalle_venta8->id_producto = 2;
        $detalle_venta8->cantidad = 1;
        $detalle_venta8->precio_unitario = 3;
        $detalle_venta8->save();

        $detalle_venta9 = new Detalle_venta();
        $detalle_venta9->num_factura = 'FAC009';
        $detalle_venta9->id_producto = 1;
        $detalle_venta9->cantidad = 1;
        $detalle_venta9->precio_unitario = 1.5;
        $detalle_venta9->save();

        $detalle_venta10 = new Detalle_venta();
        $detalle_venta10->num_factura = 'FAC010';
        $detalle_venta10->id_producto = 2;
        $detalle_venta10->cantidad = 1;
        $detalle_venta10->precio_unitario = 3;
        $detalle_venta10->save();

        DB::statement("CREATE PROCEDURE obtenerVentasPorProducto(
	IN fechaInicio DATETIME,
    IN fechaFin DATETIME,
    IN productosSeleccionados JSON)
BEGIN
	 SELECT 
        detalle_venta.id_producto, 
        SUM(detalle_venta.cantidad * detalle_venta.precio_unitario) AS total_vendido
    FROM ventas
    JOIN detalle_venta ON ventas.num_factura = detalle_venta.num_factura
    WHERE ventas.fecha_venta BETWEEN fechaInicio AND fechaFin
    AND JSON_CONTAINS(productosSeleccionados, CAST(detalle_venta.id_producto AS JSON), '$')
    GROUP BY detalle_venta.id_producto;
END");

        DB::statement('CREATE PROCEDURE mas_vendidos()
BEGIN
 SELECT 
        p.nombre AS nombre, 
        p.id_categoria as id_categoria,
        SUM(dv.cantidad) AS stock
    FROM 
        detalle_venta dv
    INNER JOIN 
        productos p ON dv.id_producto = p.id_producto
    GROUP BY 
        p.id_producto
    ORDER BY 
        stock DESC
    LIMIT 10;
END');

        DB::statement('CREATE PROCEDURE menos_vendidos()
            BEGIN
            SELECT 
                p.nombre AS nombre, 
                p.id_categoria AS id_categoria,
                COALESCE(SUM(dv.cantidad), 0) AS stock
            FROM 
                productos p
            LEFT JOIN 
                detalle_venta dv ON dv.id_producto = p.id_producto
            GROUP BY 
                p.id_producto
            ORDER BY 
                stock ASC
            LIMIT 10;
            END');
    }
}
