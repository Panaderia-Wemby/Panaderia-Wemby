@extends('layouts.app')

@section('content')
<div class="container">
        <!-- Mensajes de Éxito -->
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <!-- Mensajes de Error -->
        @if (session('errors'))
            <div class="alert alert-danger" role="alert">
                @foreach (session('errors')->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif    
    <h1>Listado de Ventas</h1>
    <div class="row">
        <div class="col-2">
            <h4>Registar Venta</h4>
            <a href="{{ route('ventas.create') }}" class="btn btn-primary">Registrar venta</a>
        </div>
        <div class="col-4">
            <h4>Buscar Venta</h4>
            <form method="GET" action="{{ route('ventas.index') }}">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" style="width: 40%" placeholder="Buscar por factura, documento o fecha" name="search" value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" type="submit" style="margin: 10px">Buscar Venta</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Número de factura</th>
                <th>Documento del cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
            <tr>
                <td>{{ $venta->num_factura }}</td>
                <td>{{ $venta->documento_cliente }}</td>
                <td>{{ $venta->fecha_venta }}</td>
                <td>${{ number_format($venta->total_venta, 2) }}</td>
                <td>
                    <a class="btn btn-info" href="{{ route('ventas.show', $venta->id_venta) }}">Detalles</a>
                    <a class="btn btn-warning" href="{{ route('ventas.edit', $venta->id_venta) }}">Editar</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
