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


        <h1>Registrar Insumo</h1>
        <div class="row">
            <div class="col-sm">
                <form action="{{ route('insumos.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="nombre">Nombre del Insumo:</label>
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
                        <label for="stock">Cantidad Inicial en Stock:</label>
                        <input type="number" min="0" style="width: 25%" class="form-control" id="stock" name="stock" required>
                    </div>
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
                        <h5 class="modal-title" id="confirmModalLabel">Confirmar Registro del Insumo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Estás a punto de registrar el siguiente Insumo:
                        <ul>
                            <li><strong>Nombre del Insumo:</strong> <span id="modalNombreInsumo"></span></li>
                            <li><strong>Categoría del insumo:</strong> <span id="modalCategoria"></span></li>
                            <li><strong>Stock inicial:</strong> <span id="modalStock"></span></li>
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

        <a href="{{ route('insumos.index') }}" class="btn btn-primary" style="margin: 10px">Volver a Insumos</a>
    </div>
    <script>

        document.addEventListener('DOMContentLoaded', () => {
            const confirmButton = document.getElementById(
                'confirmButton');

            confirmButton.addEventListener('click', () => {
                document.getElementById('modalNombreInsumo').textContent = document.getElementById(
                    'nombre').value;

                document.getElementById('modalStock').textContent = document.getElementById('stock')
                    .value;

                // Capturar y mostrar la categoría
                const categoriaSelect = document.getElementById('categoria');
                const categoriaSeleccionada = categoriaSelect.options[categoriaSelect.selectedIndex].text;
                document.getElementById('modalCategoria').textContent = categoriaSeleccionada;

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
