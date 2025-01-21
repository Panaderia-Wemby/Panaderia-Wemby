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

class InsumosController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', IsBaker::class]);

    }
    public function index(Request $request)
    {

        $categorias = Categoria::all(); // Obtener todas las categorías para el filtro dropdown

        $query = Insumos::with('categoria', 'productos', 'proveedores');

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre_insumo', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Aplicar filtro solo si 'categoria' está presente y no es vacío
        if ($request->has('categoria') && !empty($request->categoria)) {
            $query->where('id_categoria', $request->categoria);
        }

        $insumos = $query->get();

        if ($insumos->isEmpty() && ($request->has('search') || $request->has('categoria'))) {
            return redirect()->route('insumos.index')->with('error', 'Producto no encontrado');
        }

        return view('index_insumos', compact('insumos', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::all();
        $productos = Producto::all();
        $proveedores = Proveedor::all();
        return view('registrar_insumo', compact('categorias', 'productos', 'proveedores'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'nombre' => 'required|max:255',
            'categoria' => 'required|integer|exists:categorias,id_categoria',
            'stock' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $insumo = new Insumos([
                    'nombre_insumo' => $request->nombre,
                    'id_categoria' => $request->categoria,
                    'stock' => $request->stock
                ]);
                $insumo->save();

                // Asociar insumos y actualizar stocks

                if ($request->has('proveedores')) {
                    foreach ($request->proveedores as $id_proveedor) {
                        $insumo->proveedores()->attach($id_proveedor);
                    }
                }
            });

            return redirect()->route('insumos.create')->with('success', 'Insumo añadido correctamente.');
        } catch (\Exception $e) {

            return redirect()->route('insumos.create')->withErrors('Error: ' . $e->getMessage());
        }
    }

    public function editStock($id)
    {

        $insumo = Insumos::findOrFail($id);

        return view('edit-stock-insumo', compact('insumo'));
    }

    public function updateStock($id, Request $request)
    {
        $request->validate([
            'stock' => 'required|integer|min:0'
        ]);
        try {
            $insumo = Insumos::findOrFail($id);
            $insumo->stock = $request->stock;
            $insumo->save();

            return redirect()->route('insumos.index')->with('success', 'Stock actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('insumos.index')->withErrors('Error: ' . $e->getMessage());
        }
    }
}
