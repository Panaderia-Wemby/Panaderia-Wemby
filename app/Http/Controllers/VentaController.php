<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsSeller;
use App\Models\Detalle_venta;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth',IsSeller::class]);
    }
    public function index(Request $request)
    {
        
        $ventas = Venta::with('detalleVenta.producto');

        if ($request->filled('search')) {
            $search = $request->search;
            $ventas->where(function ($query) use ($search) {
                $query->where('num_factura', 'LIKE', "%{$search}%")
                    ->orWhere('documento_cliente', 'LIKE', "%{$search}%")
                    ->orWhere('fecha_venta', 'LIKE', "%{$search}%");
            });
        }

        $ventas = $ventas->get();
        return view('index_ventas', compact('ventas'));
    }
    public function cancel()
    {
        return redirect()->route('ventas.index')->with('success', 'Cancelado con éxito');
    }


    public function show($id)
    {
        $venta = Venta::with(['detalleVenta.producto', 'cajero'])->findOrFail($id);
        return view('ver_venta', compact('venta'));
    }


    public function create()
    {
        $cajeros = User::where('rol', 1)->get(); // Cajeros
        $productos = Producto::where('stock', '>', 0)->get(); // Productos disponibles

        return view('registrar_venta', compact('cajeros', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cajero_id' => 'required|exists:users,id',
            'documento_cliente' => 'required',
            'productos' => 'required|array',
            'cantidades' => 'required|array'
        ]);
        try {
            $venta = new Venta([
                'num_factura' => 'FAC-' . time(),
                'fecha_venta' => now(),
                'documento_cliente' => $request->documento_cliente,
                'id_cajero' => $request->cajero_id,
                'total_venta' => array_sum(array_map(function ($cantidad, $precio) {
                    return $cantidad * $precio;
                }, $request->cantidades, $request->precios))
            ]);
            $venta->save();

            foreach ($request->productos as $index => $producto_id) {
                $venta->detalleVenta()->create([
                    'num_factura' => $venta->num_factura,
                    'id_producto' => $producto_id,
                    'cantidad' => $request->cantidades[$index],
                    'precio_unitario' => $request->precios[$index]
                ]);

                $producto = Producto::find($producto_id);
                $producto->decrement('stock', $request->cantidades[$index]);
            }

           // Revisar si el usuario quiere una factura
        if ($request->input('generate_invoice') === 'true') {
            // Redirigir a la ruta de generación de factura con el ID de la venta
            // return $venta->id_venta;
            return redirect()->route('factura.generate', $venta->id_venta)
                ->with('success', 'Venta creada correctamente. Generando factura...');
        } else {
            // Redirigir a la página de creación de ventas
            return redirect()->route('ventas.index')->with('success', 'Venta creada correctamente');
        }
        } catch (\Exception $e) {
            return redirect()->route('ventas.create')->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $venta = Venta::with('detalleVenta.producto')->findOrFail($id);
        $productos = Producto::all();
        $cajeros = User::where('rol', 1)->get();

        return view('edit-venta', compact('venta', 'productos', 'cajeros'));
    }

    public function update(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $venta->documento_cliente = $request->documento_cliente;
            $total_venta = 0;

            foreach ($request->productos as $index => $producto_id) {
                $detalle = $venta->detalleVenta()->where('id_producto', $producto_id)->first();
                if ($detalle) {
                    $cantidad_anterior = $detalle->cantidad;
                    $cantidad_nueva = $request->cantidades[$index];


                    if ($cantidad_nueva > $cantidad_anterior) {
                        $incremento = $cantidad_nueva - $cantidad_anterior;
                        $detalle->producto->decrement('stock', $incremento);
                    }

                    $detalle->cantidad = $cantidad_nueva;
                    $detalle->save();

                    $total_venta += $detalle->precio_unitario * $detalle->cantidad;
                }
            }


            $venta->total_venta = $total_venta;
            $venta->save();

            return redirect()->route('ventas.index')->with('success', 'Venta actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('ventas.index')->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function generate(Venta $venta){
        $cajero = User::find($venta->id_cajero);
        $productosVendidos = Detalle_venta::where('num_factura', '=', $venta->num_factura)->get();
        $productos = [];
        foreach($productosVendidos as $produc){
            $producto = Producto::find($produc->id_producto);
            $productos[] = $producto;
        }
        return view('layouts.factura', compact('cajero', 'productosVendidos', 'productos', 'venta'));
    }
}
