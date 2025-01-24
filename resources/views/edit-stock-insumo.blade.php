@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">

            <h2>Actualizar Stock del Insumo: {{ $insumo->nombre_insumo }}</h2>
            <div class="col-sm">
                <div>
                    <strong>Stock Anterior:</strong> {{ $insumo->stock }}
                </div>
            </div>
            <div class="col-sm">
                <form action="{{ route('insumos.update-stock', $insumo->id_insumo) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="stock"><h4>Nuevo Stock</h4></label>
                        <input type="number" min="0" class="form-control" id="stock" name="stock" style="width: 25%"
                            value="{{ $insumo->stock }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin: 10px">Actualizar</button>
                </form>
            </div>
        </div>
        <a href="{{ route('insumos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Insumos</a>
    </div>
@endsection
