@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Actualizar Venta</h1>
        <form action="{{ route('ventas.update', $venta->id_venta) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="documento_cliente"><h5>Documento del Cliente:</h5></label>
                <input type="text" class="form-control" id="documento_cliente" name="documento_cliente" style="width: 35%"
                    value="{{ $venta->documento_cliente }}" required>
            </div>

            <div class="form-group">
                <label><h4>Productos:</h4></label>
                @foreach ($venta->detalleVenta as $detalle)
                    <div class="row">
                        <div class="col">
                            <h5>Producto</h5>
                            <select style="width: 75%" name="productos[]" class="form-control">
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id_producto }}"
                                        {{ $detalle->id_producto == $producto->id ? 'selected' : '' }}>
                                        {{ $producto->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <h5>Cantidad</h5>
                            <input type="number" name="cantidades[]" value="{{ $detalle->cantidad }}" class="form-control"
                                min="1" max="{{ $detalle->producto->stock }}">
                        </div>
                        <div class="col">
                            <h5>Precio unitario</h5>
                            <input type="text" class="form-control" value="{{ $detalle->precio_unitario }}" readonly>
                        </div>
                        <div class="col">
                            <h5>Stock</h5>
                            <input type="text" class="form-control" value="{{ $detalle->producto->stock }}" readonly>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="form-group">
                <label><h4>Total de la Venta:</h4></label>
                <input type="text" style="width: 15%" class="form-control" name="total_venta" value="{{ $venta->total_venta }}" readonly>
            </div>
            <button type="submit" style="margin: 10px" class="btn btn-primary">Actualizar Venta</button>
        </form>
    </div>
@endsection
