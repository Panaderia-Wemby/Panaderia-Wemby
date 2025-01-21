@extends('layouts.app')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

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
    <div class="container mt-5">
        <h1 class="mb-4">Gráficas de Ventas</h1>

        <!-- Botón para volver al formulario -->
        <a href="{{ route('form') }}" class="btn btn-secondary mb-3">Volver a los filtros</a>

        <!-- Contenedor para la gráfica -->
        <div class="card">
            <h2 class="mt-5">Gráfica de {{ ucfirst($tipoGrafica) }}</h2>
            <div class="card-body">
                <canvas id="grafica" class="my-4" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <button class="btn btn-primary print-btn" onclick="window.print()" style="margin-top: 20px;">Imprimir</button>
    <a href="{{ route('form') }}" class="btn btn-secondary">Volver</a>
@endsection

@section('scripts')
    <script>
        const ctx = document.getElementById('grafica').getContext('2d');
        const grafica = new Chart(ctx, {
            type: '{{ $tipoGrafica }}', // Tipo de gráfica: 'bar', 'line', 'pie'
            data: {
                labels: @json($labels), // Etiquetas de la gráfica (productos)
                datasets: [{
                    label: 'Total Vendido',
                    data: @json($data), // Totales de ventas por producto
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return '€ ' + tooltipItem.raw.toFixed(2); // Mostrar en formato de precio
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
