@if($productos->count() > 0)
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Proveedor</th>
                    <th>Stock</th>
                    <th>Unidad/Caja</th>
                    <th>Atributos</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $producto)
                    <tr>
                        <td><strong>{{ $producto->codigo }}</strong></td>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->categoria->nombre }}</td>
                        <td>{{ $producto->proveedor->razon_social }}</td>
                        <td>{{ $producto->stock }}</td>
                        <td>{{ $producto->unidades_por_caja }}</td>
                        <td>
                            @foreach($producto->atributos as $attr)
                                <small class="d-block">{{ ucfirst($attr->clave) }}: {{ $attr->valor }}</small>
                            @endforeach
                        </td>
                        <td>{{ Str::limit($producto->descripcion, 50) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-center text-muted">No hay productos registrados.</p>
@endif
