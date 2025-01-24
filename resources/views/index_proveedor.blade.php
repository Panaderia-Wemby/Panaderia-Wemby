@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Listado de Proveedores</h2>
        <form action="{{ route('proveedores.index') }}" method="GET">
            <div class="form-group">
                <label for="search"><h4>Buscar proveedor:</h4></label>
                <input type="text" class="form-control" id="search" name="search" style="width: 25%" placeholder="Nombre del proveedor">
            </div>
            <button type="submit" class="btn btn-primary" style="margin: 10px">Buscar</button>
        </form>

    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Productos que provee</th>
                <th>Insumos que provee</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($proveedores as $proveedor)
            <tr>
                <td>{{ $proveedor->nombre }}</td>
                <td>{{ $proveedor->contacto }}</td>
                <td>
                    @foreach ($proveedor->productos as $producto)
                        <h5><span class="badge bg-primary">{{ $producto->nombre }}</span></h5>
                    @endforeach
                </td>
                <td>
                    @foreach ($proveedor->insumos as $insumo)
                        <h5><span class="badge bg-primary">{{ $insumo->nombre_insumo }}</span></h5>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-sm">
            <a href="{{ route('productos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Productos</a>
        </div>
        <div class="col-sm">
            <a href="{{ route('insumos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Insumos</a>
        </div>
    </div>
    
</div>
@endsection
