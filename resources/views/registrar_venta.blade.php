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
        <h2>Crear Venta</h2>
        <form action="{{ route('ventas.store') }}" method="POST">
            <div class="row">
                <div class="col-sm">
                    @csrf
                    <div class="form-group">
                        <label for="cajero_id">
                            <h5>Cajero</h5>
                        </label>
                        <select class="form-control" id="cajero_id" name="cajero_id">
                            @foreach ($cajeros as $cajero)
                                <option value="{{ $cajero->id }}">{{ $cajero->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="documento_cliente">
                            <h5>Documento del Cliente</h5>
                        </label>
                        <input type="text" class="form-control" id="documento_cliente" name="documento_cliente" required>
                    </div>
                </div>
                <div class="col-sm">
                    <div id="productos-container">
                        <div class="form-group producto-item">
                            <label for="productos">Productos</label>
                            <select class="form-control producto-select" name="productos[]"
                                onchange="updatePriceAndStock(this)">
                                <option value="">Seleccione un producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id_producto }}" data-price="{{ $producto->precio }}"
                                        data-stock="{{ $producto->stock }}">
                                        {{ $producto->nombre }} - ${{ $producto->precio }} - Stock: {{ $producto->stock }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="precios[]" class="producto-precio" value="0">
                            <label for="cantidad">Cantidad</label>
                            <input type="number" class="form-control cantidad" name="cantidades[]" min="1" required
                                onchange="updateTotal()">
                        </div>

                    </div>
                    <button type="button" class="btn btn-secondary" style="margin: 10px; width: 25%"
                        onclick="addProduct()">Añadir Producto</button>
                </div>
            </div>

            <div class="form-group">
                <strong>Total de la Venta: <span id="totalVenta">$0</span></strong>
            </div>
            <div class="row">
                <div class="col-sm">
                    <button type="button" class="btn btn-primary" style="margin: 10px" data-bs-toggle="modal" data-bs-target="#facturaModal">Confirmar Venta</button>
                </div>
                <div class="col-sm">
                    <button type="button" style="margin: 10px" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        Cancelar Venta
                    </button>
                </div>
                <div class="col-sm">
                    <a href="{{ route('ventas.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Ventas</a>
                </div>
            </div>

            <div class="modal fade" id="facturaModal" tabindex="-1" aria-labelledby="facturaModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="facturaModalLabel">Factura</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">2
                            ¿Desea Generar una factura de esta compra?
                        </div>
                        
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-danger" onclick="document.getElementById('generate_invoice').value = 'false';">
                                No, solo venta
                            </button>
                            <!-- Botón para enviar el formulario con factura -->
                            <button type="submit" class="btn btn-success" onclick="document.getElementById('generate_invoice').value = 'true';">
                                Sí
                            </button>
                        </div>
                        {{-- <form id="cancel-form" action="{{ route('ventas.cancel') }}" method="POST" style="display: none;">
                            @csrf
                        </form> --}}
                    </div>
                </div>
            </div>

             <!-- Campo oculto para indicar si se genera factura o no -->
            <input type="hidden" id="generate_invoice" name="generate_invoice" value="false">

        </form>
    </div>

    <!-- Modal de Generar Factura -->
    

    <!-- Modal de Cancelar Venta -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Confirmar Cancelación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de querer cancelar esta venta?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <a href="{{ route('ventas.index') }}" class="btn btn-danger"
                    onclick="event.preventDefault(); document.getElementById('cancel-form').submit();">Sí</a>
                </div>
                <form id="cancel-form" action="{{ route('ventas.cancel') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>


    <script>
        let productos = @json($productos);

        function addProduct() {
            let container = document.getElementById('productos-container');
            let firstProduct = container.querySelector('.producto-item');
            let newProduct = firstProduct.cloneNode(true);
            newProduct.querySelector('.producto-select').selectedIndex = 0;
            newProduct.querySelector('.cantidad').value = '';
            container.appendChild(newProduct);
        }

        function updatePriceAndStock(select) {
            let price = select.options[select.selectedIndex].dataset.price;
            let stock = select.options[select.selectedIndex].dataset.stock;
            let precioInput = select.closest('.producto-item').querySelector('.producto-precio');
            precioInput.value = price; // Actualiza el campo oculto con el precio
            let cantidadInput = select.closest('.producto-item').querySelector('.cantidad');
            cantidadInput.max = stock;
            updateTotal();
        }

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.producto-item').forEach(item => {
                let price = item.querySelector('.producto-select').selectedOptions[0].dataset.price || 0;
                let cantidad = item.querySelector('.cantidad').value || 0;
                total += price * cantidad;
            });
            document.getElementById('totalVenta').textContent = `$${total.toFixed(2)}`;
        }

        document.getElementById('productos-container').addEventListener('change', function(event) {
            if (event.target.classList.contains('cantidad')) {
                updateTotal();
            }
        });
        function submitVenta(generateInvoice) {
        document.getElementById('generateInvoice').value = generateInvoice;
        document.getElementById('ventaForm').submit();
    }
    </script>
@endsection
