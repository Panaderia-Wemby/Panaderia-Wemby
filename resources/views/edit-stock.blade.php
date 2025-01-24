@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">

            <h2>Actualizar Stock del Producto: {{ $producto->nombre }}</h2>
            <div class="col-sm">
                <div>
                    <strong>Stock Anterior:</strong> {{ $producto->stock }}
                </div>
                <div>
                    <strong>Insumos Usados:</strong>
                    <ul>
                        @foreach ($producto->insumos as $insumo)
                            <li>{{ $insumo->nombre_insumo }}: {{ $insumo->pivot->cantidad_usada }} usado(s)</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <strong>Insumos Totales Disponibles:</strong>
                    <ul>
                        @foreach ($insumosTotales as $insumo)
                            <li>{{ $insumo->nombre_insumo }}: {{ $insumo->stock }} disponible(s)</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-sm">
                <form action="{{ route('productos.update-stock', $producto->id_producto) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="stock"><h4>Nuevo Stock</h4></label>
                        <input type="number" min="0" class="form-control" id="stock" name="stock" style="width: 25%"
                            value="{{ $producto->stock }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin: 10px">Actualizar</button>
                </form>
            </div>
        </div>
        <a href="{{ route('productos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Productos</a>
    </div>
@endsection
