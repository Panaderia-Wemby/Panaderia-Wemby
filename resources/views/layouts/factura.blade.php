<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $venta->num_factura }}</title>
    <!-- Agregar Bootstrap desde CDN -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        /* Estilos personalizados */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
        }

        /* Media Query para ocultar el botón en pantallas pequeñas */
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div id="invoice">
        <h2>{{ __('Panaderia Wemby') }}</h2>
        <p><b>Número de Factura:</b> {{ $venta->num_factura }}</p>
        <p><b>Cédula del Cliente:</b> {{ $venta->documento_cliente }}</p>
        <p><b>Cajero:</b> {{ $cajero->name }}</p>
        <p><b>Fecha de Venta:</b> {{ $venta->fecha_venta }}</p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {{-- 'cajero', 'productosVendidos', 'productos', 'venta' --}}
                @for ($i = 0; $i < count($productos); $i++)
                    <tr>
                        <td>{{ $productos[$i]->nombre }}</td>
                        <td>{{ $productosVendidos[$i]->cantidad }}</td>
                        <td>$ {{ $productos[$i]->precio }}</td>
                        <td>$ {{ $productosVendidos[$i]->cantidad * $productos[$i]->precio }}</td>
                    </tr>
                @endfor

            </tbody>
        </table>

        <div class="total">
            Total a Pagar: $ {{ $venta->total_venta }}
        </div>
    </div>

    <!-- Botón para imprimir, solo visible en pantallas grandes -->
    <button class="btn btn-primary print-btn" onclick="window.print()" style="margin-top: 20px;">Imprimir
        Factura</button>
    <a class="btn btn-secondary print-btn" style="margin-top: 20px;" href="{{ route('ventas.index') }}">Regresar</a>
</body>

</html>
