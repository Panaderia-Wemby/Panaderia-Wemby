<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsBoss;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Insumos;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth',IsBoss::class]);
    }
    public function index(Request $request)
    {
        $query = Proveedor::with(['productos', 'insumos']);

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'LIKE', '%' . $request->search . '%');
            });
        }

        $proveedores = $query->get();

        if($proveedores->isEmpty() && ($request->has('search'))) {
            return redirect()->route('proveedor.index')->with('error', 'Proveedor no encontrado');
        }

        return view('index_proveedor', compact('proveedores'));
    }
}
