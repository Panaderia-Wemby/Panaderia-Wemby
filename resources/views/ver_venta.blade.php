@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Detalle de Venta</h1>
    <div class="card">
        <div class="card-header">
            <h3>Venta #{{ $venta->num_factura }}</h3>
        </div>
        <div class="card-body">
            <h5><strong>Cajero: </strong>{{ $venta->cajero->name }}</h5>
            <h5><strong>Fecha: </strong>{{ $venta->fecha_venta }}</h5>
            <h5><strong>Total de la venta: </strong>${{ number_format($venta->total_venta, 2) }}</h5>

            <h4>Productos vendidos:</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th><h5>Producto</h5></th>
                        <th><h5>Cantidad</h5></th>
                        <th><h5>Precio Unitario</h5></th>
                        <th><h5>Subtotal</h5></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalleVenta as $detalle)
                    <tr>
                        <td><h6>{{ $detalle->producto->nombre }}</h6></td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <a href="{{ route('ventas.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Ventas</a>
</div>
@endsection
