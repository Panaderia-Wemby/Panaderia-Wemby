@extends('layouts.app')

@section('style')
    <style>
        @media print {

            .select-range {
                display: none !important;
            }

            .wrapper.row1,
            .bgded.overlay {
                display: none !important;
            }

            /* Oculta elementos innecesarios */
            button,
            .no-print {
                display: none;
            }

            .btn {
                display: none !important;
            }

        }
    </style>
@endsection

@section('content')
    <div class="container">
        <h2>Resultado del análisis: {{ ucfirst(str_replace('_', ' ', $tipoAnalisis)) }}</h2>
        <hr>
        @if (!$resultado)
            <p>No se encontraron resultados para el análisis seleccionado.</p>
        @elseif ($tipoAnalisis == 'productos_mas_vendidos')
            @php
                $mas_vendidos = $resultado['mas_vendidos'];
                $menos_vendidos = $resultado['menos_vendidos'];
            @endphp
            <h2>Productos mas vendidos</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre del producto</th>
                        <th>Cantidad de unidades vendidas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mas_vendidos as $item)
                        <tr>
                            @if (isset($categoria))
                                @if ($item->id_categoria == $categoria)
                                    <td>{{ $item->nombre }}</td>
                                    <td>{{ $item->stock }}</td>
                                @endif
                            @else
                                <td>{{ $item->nombre }}</td>
                                <td>{{ $item->stock }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <br>
            <br>
            <hr>
            <h2>Productos menos vendidos</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre del producto</th>
                        <th>Cantidad de unidades vendidas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menos_vendidos as $item)
                        <tr>
                            @if (isset($categoria))
                                @if ($item->id_categoria == $categoria)
                                    <td>{{ $item->nombre }}</td>
                                    <td>{{ $item->stock }}</td>
                                @endif
                            @else
                                <td>{{ $item->nombre }}</td>
                                <td>{{ $item->stock }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        @if ($tipoAnalisis == 'ventas_altas')
                            <th>Número de factura</th>
                            <th>Fecha de venta</th>
                            <th>Total de venta</th>
                        @elseif($tipoAnalisis == 'productos_mas_vendidos' || $tipoAnalisis == 'stock_bajo')
                            <th>Nombre del producto</th>
                            <th>Cantidad</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resultado as $item)
                        <tr>
                            @if ($tipoAnalisis == 'ventas_altas')
                                <td>{{ $item->num_factura }}</td>
                                <td>{{ $item->fecha_venta }}</td>
                                <td>${{ number_format($item->total_venta, 2) }}</td>
                            @elseif($tipoAnalisis == 'stock_bajo')
                                @if (isset($categoria))
                                    @if ($item->id_categoria == $categoria)
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->stock }}</td>
                                    @endif
                                @else
                                    <td>{{ $item->nombre }}</td>
                                    <td>{{ $item->stock }}</td>
                                @endif
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <button class="btn btn-primary print-btn" onclick="window.print()" style="margin-top: 20px;">Imprimir</button>
        <a href="{{ route('analisis.form') }}" class="btn btn-secondary">Volver</a>
    </div>
@endsection
