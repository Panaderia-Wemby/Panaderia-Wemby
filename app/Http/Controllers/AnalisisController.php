<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\IsBoss;
use App\Models\Categoria;
use App\Models\Detalle_venta;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AnalisisController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', IsBoss::class]);
    }
    public function showAnalysisForm()
    {
        $categorias = Categoria::all();
        return view('analisis.index', compact('categorias'));
    }

    public function generateAnalysis(Request $request)
    {
        $tipoAnalisis = $request->input('tipo_analisis');
        $fechaInicio = $request->input('fecha_inicio')
            ? Carbon::parse($request->input('fecha_inicio'))
            : Carbon::now()->startOfMonth();

        $fechaFin = $request->input('fecha_fin')
            ? Carbon::parse($request->input('fecha_fin'))
            : Carbon::now()->endOfMonth();
        $categoria = $request->input('categoria');

        // Inicializa la variable para almacenar el resultado
        $resultado = [];

        switch ($tipoAnalisis) {
            case 'ventas_altas':
                // Ventas más altas en el rango de fechas
                $resultado = Venta::select('num_factura', 'fecha_venta', 'total_venta')
                    ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
                    ->orderByDesc('total_venta')
                    ->take(10) // Muestra las 10 ventas más altas
                    ->get();
                break;

            case 'productos_mas_vendidos':
                // Productos más vendidos en el rango de fechas y categoría seleccionada
                $resultado['mas_vendidos'] = DB::select("call mas_vendidos()");
                $resultado['menos_vendidos'] = DB::select("call menos_vendidos()");
                break;

            case 'stock_bajo':
                // Productos con stock bajo
                $resultado = Producto::where('stock', '<', 10) // Por ejemplo, menos de 10 en stock
                    ->orderBy('stock')
                    ->get();
                break;

            default:
                // Si no se selecciona ningún análisis válido, se muestra un mensaje de error
                return redirect()->back()->with('error', 'Tipo de análisis no válido.');
        }
        // return $resultado['mas_vendidos'];
        return view('analisis.resultado', compact('resultado', 'tipoAnalisis', 'categoria'));
    }
}
