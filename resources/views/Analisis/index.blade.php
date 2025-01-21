@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Análisis y reportes</h2>

    <form action="{{ route('analisis.generate') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="tipo_analisis">Seleccione el tipo de análisis</label>
            <select name="tipo_analisis" id="tipo_analisis" class="form-control">
                <option value="ventas_altas">Ventas más altas</option>
                <option value="productos_mas_vendidos">Productos más vendidos</option>
                <option value="stock_bajo">Productos con stock bajo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha_inicio">Fecha de inicio (opcional)</label>
            <input type="date" name="fecha_inicio" class="form-control">
        </div>

        <div class="form-group">
            <label for="fecha_fin">Fecha de fin (opcional)</label>
            <input type="date" name="fecha_fin" class="form-control">
        </div>

        <div class="form-group">
            <label for="categoria">Categoría de productos (opcional)</label>
            <select name="categoria" id="categoria" class="form-control">
                <option value="">Seleccionar categoría</option>
                <!-- Aquí se añadirían opciones de categoría -->
                @foreach ($categorias as $categoria)
                <option value="{{$categoria->id_categoria}}">{{$categoria->nombre_categoria}}</option>
                @endforeach
            </select>
        </div>
        <br>
        <button type="submit" class="btn btn-primary">Generar análisis</button>
    </form>
</div>
@endsection
