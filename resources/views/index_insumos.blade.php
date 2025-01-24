{{-- resources/views/productos/index_producto.blade.php --}}
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
        <h1>Listado de Insumos</h1>
        <div class="row">
            <div class="col-sm">
                <h4>Registar insumo</h4>
                <a href="{{ route('insumos.create') }}" class="btn btn-primary">Registrar Insumo</a>
            </div>
            <div class="col-sm">
                <form action="{{ route('insumos.index') }}" method="GET">
                    <div class="form-group">
                        <label for="categoria"> <h4>Filtrar por Categoría:</h4></label>
                        <select class="form-control" id="categoria" name="categoria">
                            <option value="">Sin filtrar</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id_categoria }}"
                                    {{ request('categoria') == $categoria->id_categoria ? 'selected' : '' }}>
                                    {{ $categoria->nombre_categoria }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="col-sm">
                <form action="{{ route('insumos.index') }}" method="GET">
                    <div class="form-group">
                        <label for="search"><h4>Buscar Insumo</h4></label>
                        <input type="text" class="form-control" id="search" name="search"
                            placeholder="Nombre del insumo">
                    </div>
                    <button type="submit" class="btn btn-primary"  style="margin: 10px">Buscar Insumo</button>
                </form>
            </div>
            <div class="col-sm">
                <h4>Mostrar proveedores</h4>
                <a href="{{ route('proveedores.index') }}" class="btn btn-primary">Mostrar proveedores</a>
            </div>

        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Proveedores</th>
                    <th>Productos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($insumos as $insumo)
                    <tr>
                        <td>{{ $insumo->nombre_insumo }}</td>
                        <td>{{ $insumo->categoria->nombre_categoria ?? 'Sin categoría' }}</td>
                        <td>
                            {{ $insumo->stock }}
                        </td>
                        <td>
                            @if ($insumo->proveedores->isEmpty())
                                <span class="text-muted">Sin proveedores</span>
                            @else
                                @foreach ($insumo->proveedores as $proveedor)
                                <h5><span class="badge bg-primary">{{ $proveedor->nombre }}</span></h5>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @if ($insumo->productos->isEmpty())
                                <span class="text-muted">Sin productos</span>
                            @else
                                @foreach ($insumo->productos as $producto)
                                    <h5><span class="badge bg-primary">{{ $producto->nombre }}:
                                        {{ $producto->pivot->cantidad_usada }}</span></h5>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('insumos.edit-stock', $insumo->id_insumo) }}"
                                class="btn btn-info">Editar
                                Stock</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        document.getElementById('categoria').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
@endsection
