<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsBaker;
use App\Http\Middleware\IsBoss;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Insumos;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', IsBaker::class]);
    }

    public function index(Request $request)
    {
        $categorias = Categoria::all(); // Obtener todas las categorías para el filtro dropdown

        $query = Producto::with('categoria', 'insumos', 'proveedores');

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('codigo_producto', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Aplicar filtro solo si 'categoria' está presente y no es vacío
        if ($request->has('categoria') && !empty($request->categoria)) {
            $query->where('id_categoria', $request->categoria);
        }

        $productos = $query->get();

        if($productos->isEmpty() && ($request->has('search') || $request->has('categoria'))) {
            return redirect()->route('productos.index')->with('error', 'Producto no encontrado');
        }

        return view('index_producto', compact('productos', 'categorias'));
    }
    public function create()
    {
        $categorias = Categoria::all();
        $insumos = Insumos::all();
        $proveedores = Proveedor::all();
        return view('registrar_producto', compact('categorias', 'insumos', 'proveedores'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'codigo' => 'required|unique:productos,codigo_producto|max:255',
            'nombre' => 'required|max:255',
            'categoria' => 'required|integer|exists:categorias,id_categoria',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $producto = new Producto([
                    'codigo_producto' => $request->codigo,
                    'nombre' => $request->nombre,
                    'id_categoria' => $request->categoria,
                    'precio' => $request->precio,
                    'stock' => $request->stock
                ]);
                $producto->save();

                // Asociar insumos y actualizar stocks

                if ($request->has('insumos')) {
                    foreach ($request->insumos as $id_insumo) {
                        $cantidad_usada = $request->cantidad_insumo[$id_insumo] ?? 0;
                        $total_necesario = $cantidad_usada * $request->stock; // Multiplicar la cantidad necesaria por el stock del producto

                        $insumo = Insumos::find($id_insumo);
                        if ($insumo && $insumo->stock >= $total_necesario) {
                            $producto->insumos()->attach($id_insumo, ['cantidad_usada' => $cantidad_usada]);
                            $insumo->decrement('stock', $total_necesario);
                        } else {
                            throw new \Exception("No hay suficientes insumos disponibles");
                        }
                    }
                }


                if ($request->has('proveedores')) {
                    foreach ($request->proveedores as $id_proveedor) {
                        $producto->proveedores()->attach($id_proveedor);
                    }
                }
            });

            return redirect()->route('productos.create')->with('success', 'Producto añadido correctamente.');
        } catch (\Exception $e) {

            return redirect()->route('productos.create')->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function editStock($id)
    {
        $producto = Producto::with(['insumos' => function ($query) {
            $query->withPivot('cantidad_usada');
        }])->findOrFail($id);

        $insumosTotales = Insumos::select('id_insumo', 'nombre_insumo', 'stock')->get();
        return view('edit-stock', compact('producto', 'insumosTotales'));
    }
    
    public function updateStock($id, Request $request)
    {
        $request->validate([
            'stock' => 'required|integer|min:0'
        ]);
        try {
            $producto = Producto::findOrFail($id);
            $producto->stock = $request->stock;
            $producto->save();
        
            return redirect()->route('productos.index')->with('success', 'Stock actualizado correctamente.');
        }catch (\Exception $e) {
            return redirect()->route('productos.index')->withErrors('Error: ' . $e->getMessage());
        }

    }
    
}
