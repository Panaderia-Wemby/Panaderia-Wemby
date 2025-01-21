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

            .btn-primary {
                display: none !important;
            }

            /* Ajusta el tamaño de la gráfica para que se vea bien en la impresión */
            .chart-container {
                width: 100% !important;
                height: auto !important;
            }
        }
    </style>
@endsection
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">

                        <h1>Gráfica de Ventas</h1>

                        <!-- Filtro de rango de tiempo -->
                        <form method="GET" action="{{ route('graficos.ventas') }}">
                            <select name="range" class="form-select select-range" onchange="this.form.submit()">
                                <option value="daily" {{ $range == 'daily' ? 'selected' : '' }}>Diario</option>
                                <option value="weekly" {{ $range == 'weekly' ? 'selected' : '' }}>Semanal</option>
                                <option value="monthly" {{ $range == 'monthly' ? 'selected' : '' }}>Mensual</option>
                            </select>
                        </form>

                        <div>
                            {!! $chart->renderHtml() !!} <!-- Renderiza el gráfico aquí -->
                        </div>
                        <div>
                            <br>
                            <p style="font-size: 20px"><b><i>Total de ganancias de Ventas:</i></b>
                                ${{ number_format($total_ventas, 2) }}</p>
                            <!-- Aquí podrías añadir más información como el total de stock -->
                        </div>
                        <br>
                        <div>
                            <div>
                                <h3>Resumen de venta diaria</h3>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Documento Cliente</th>
                                        <th>ID Cajero</th>
                                        <th>Total Venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ventas as $venta)
                                        <tr>
                                            <td>{{ $venta->num_factura }}</td>
                                            <td>{{ $venta->fecha_venta }}</td>
                                            <td>{{ $venta->documento_cliente }}</td>
                                            <td>{{ $venta->id_cajero }}</td>
                                            <td>${{ number_format($venta->total_venta, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <br>
                <button class="btn btn-primary" onclick="printReport()">Imprimir Reporte</button>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('scripts')
    {!! $chart->renderChartJsLibrary() !!}
    {!! $chart->renderJs() !!}
    <script>
        function printReport() {
            window.print();
        }
    </script>
@endsection
