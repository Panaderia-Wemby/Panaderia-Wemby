{{-- resources/views/registrar_producto.blade.php --}}

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


        <h1>Registrar Producto</h1>
        <div class="row">
            <div class="col-sm">
                <form action="{{ route('productos.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="codigo_producto">Código del Producto:</label>
                        <input type="text" class="form-control" id="codigo_producto" name="codigo" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría:</label>
                        <select class="form-control" id="categoria" name="categoria">
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id_categoria }}">{{ $categoria->nombre_categoria }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio:</label>
                        <input type="number" min="1" style="width: 35%" step="0.01" class="form-control" id="precio"
                            name="precio" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Cantidad Inicial en Stock:</label>
                        <input type="number" min="0" style="width: 25%" class="form-control" id="stock" name="stock" required>
                    </div>
            </div>
            <div class="col-sm">
                <h3>Insumos Requeridos</h3>
                @foreach ($insumos as $insumo)
                    <div class="form-group form-check">
                        <input class="form-check-input" type="checkbox" value="{{ $insumo->id_insumo }}"
                            id="insumo{{ $insumo->id_insumo }}" name="insumos[]"
                            onchange="toggleInsumoInput(this, '{{ $insumo->id_insumo }}')">
                        <label class="form-check-label" for="insumo{{ $insumo->id_insumo }}">
                            {{ $insumo->nombre_insumo }} (Stock restante: {{ $insumo->stock }})
                        </label>
                        <input type="number" min="1" class="form-control insumo-cantidad"
                            name="cantidad_insumo[{{ $insumo->id_insumo }}]" id="cantidad_insumo{{ $insumo->id_insumo }}"
                            placeholder="Cantidad" disabled style="width: 100px; display: inline-block; margin-left: 10px;">
                    </div>
                @endforeach
            </div>
            <div class="col-sm">
                <h3>Proveedores del producto si es que aplica</h3>
                @foreach ($proveedores as $proveedor)
                    <div class="form-group form-check">
                        <input class="form-check-input" type="checkbox" value="{{ $proveedor->id_proveedor }}"
                            id="proveedor{{ $proveedor->id_proveedor }}" name="proveedores[]">
                        <label class="form-check-label" for="insumo{{ $proveedor->id_proveedor }}">
                            {{ $proveedor->nombre }}
                        </label>

                    </div>
                @endforeach
            </div>
        </div>
        <!-- Botón para abrir el modal -->
        <button type="button" id="confirmButton" class="btn btn-primary" data-bs-toggle="modal"
            data-bs-target="#confirmModal">
            Revisar antes de guardar
        </button>

        <!-- Modal -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Confirmar Registro del Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Estás a punto de registrar el siguiente producto:
                        <ul>
                            <li><strong>Código del Producto:</strong> <span id="modalCodigoProducto"></span></li>
                            <li><strong>Nombre del Producto:</strong> <span id="modalNombreProducto"></span></li>
                            <li><strong>Categoría del producto:</strong> <span id="modalCategoria"></span></li>
                            <li><strong>Precio:</strong> <span id="modalPrecio"></span></li>
                            <li><strong>Stock inicial:</strong> <span id="modalStock"></span></li>
                            <li><strong>Insumos Elegidos:</strong>
                                <ul id="modalInsumos"></ul>
                            </li>
                            <li><strong>Proveedores Elegidos:</strong>
                                <ul id="modalProveedores"></ul>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
        </form>

        <a href="{{ route('productos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Productos</a>
    </div>
    <script>
        toggleInsumoInput = (checkbox, id) => {
            const inputCantidad = document.getElementById('cantidad_insumo' + id);
            inputCantidad.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                inputCantidad.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const confirmButton = document.getElementById(
                'confirmButton');

            confirmButton.addEventListener('click', () => {
                document.getElementById('modalCodigoProducto').textContent = document.getElementById(
                    'codigo_producto').value;
                document.getElementById('modalNombreProducto').textContent = document.getElementById(
                    'nombre').value;
                document.getElementById('modalPrecio').textContent = document.getElementById('precio')
                    .value;

                document.getElementById('modalStock').textContent = document.getElementById('stock')
                    .value;

                // Capturar y mostrar la categoría
                const categoriaSelect = document.getElementById('categoria');
                const categoriaSeleccionada = categoriaSelect.options[categoriaSelect.selectedIndex].text;
                document.getElementById('modalCategoria').textContent = categoriaSeleccionada;

                // Capturar y mostrar insumos seleccionados y sus cantidades
                const insumos = document.querySelectorAll('input[name="insumos[]"]:checked');
                const listaInsumos = document.getElementById('modalInsumos');
                listaInsumos.innerHTML = ''; // Limpiar la lista anterior
                insumos.forEach(function(insumo) {
                    const cantidad = document.getElementById('cantidad_insumo' + insumo.value)
                        .value;
                    const item = document.createElement('li');
                    item.textContent = insumo.nextElementSibling.textContent.trim() +
                        ' - Cantidad: ' + cantidad;
                    listaInsumos.appendChild(item);
                });
                if (insumos.length <= 0) {
                    const item = document.createElement('li');
                    item.textContent = `No hay insumos seleccionados`;
                    listaInsumos.appendChild(item);
                }
                // Capturar y mostrar proveedores seleccionados
                const proveedores = document.querySelectorAll('input[name="proveedores[]"]:checked');
                const listaProveedores = document.getElementById('modalProveedores');
                listaProveedores.innerHTML = ''; // Limpiar la lista anterior
                proveedores.forEach(function(proveedor) {
                    const item = document.createElement('li');
                    item.textContent = proveedor.nextElementSibling.textContent.trim();
                    listaProveedores.appendChild(item);
                });

                if (proveedores.length <= 0) {
                    const item = document.createElement('li');
                    item.textContent = `No hay proveedores seleccionados`;
                    listaProveedores.appendChild(item);
                }

            });
        });
    </script>
@endsection
