@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <h1 class="mb-4">Generar Gráficas de Ventas</h1>

        <!-- Formulario para seleccionar los filtros -->
        <form action="{{ route('generate.graph') }}" method="GET">
            @csrf

            <!-- Filtro de Rango de Fechas -->
            <div class="mb-3">
                <label for="rango_fechas" class="form-label">Rango de Fechas:</label>
                <div class="input-group">
                    <input type="date" name="rango_fechas[inicio]" class="form-control" required>
                    <input type="date" name="rango_fechas[fin]" class="form-control" required>
                </div>
            </div>

            <!-- Filtro de Productos -->
            <div class="mb-3">
                <label for="productos" class="form-label">Productos:</label>
                <select name="productos[]" class="form-select" multiple="multiple" required>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id_producto }}">{{ $producto->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro de Tipo de Gráfica -->
            <div class="mb-3">
                <label for="tipo_grafica" class="form-label">Tipo de Gráfica:</label>
                <select name="tipo_grafica" class="form-select" required>
                    <option value="bar">Barras</option>
                    <option value="line">Líneas</option>
                    <option value="pie">Torta</option>
                </select>
            </div>

            <!-- Botón para generar la gráfica -->
            <button type="submit" class="btn btn-primary">Generar Gráfica</button>
        </form>
    </div>
@endsection
