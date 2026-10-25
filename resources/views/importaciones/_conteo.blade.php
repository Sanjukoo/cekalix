<div class="card card-custom mt-3">
    <div class="card-header">Conteo físico en descarga</div>

    <div class="card-body">
        @if($importacion->detalles->isNotEmpty())
            <p class="text-muted" id="ayuda-conteo">
                Registra las cajas recibidas para cada SKU; usa 0 si no llegó ninguna.
                Diferencia = Cajas Contadas - Cajas Facturadas.
            </p>

            @php
                $erroresConteo = collect($errors->get('conteos'))
                    ->merge(collect($errors->get('conteos.*'))->flatten());
            @endphp

            @if($erroresConteo->isNotEmpty())
                <div class="alert alert-danger" role="alert">
                    <strong>No se pudo guardar el conteo.</strong>
                    <ul class="mb-0">
                        @foreach($erroresConteo as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $editable = $importacion->estado === 'En recepción';
            @endphp

            @if(!$editable)
                <p class="alert alert-info">
                    El conteo y las mermas solo pueden editarse mientras la importación está en recepción.
                </p>
            @endif

            <form action="{{ route('importaciones.conteo.update', $importacion) }}"
                  method="POST"
                  aria-describedby="ayuda-conteo">
                @csrf
                @method('PATCH')

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th scope="col">SKU</th>
                                <th scope="col">Producto</th>
                                <th scope="col">Cajas Facturadas</th>
                                <th scope="col">Cajas Contadas</th>
                                <th scope="col">Diferencia</th>
                                <th scope="col">Cajas Dañadas</th>
                                <th scope="col">Unidades a Stock</th>

                                @if($editable)
                                    <th scope="col">Acciones</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($importacion->detalles as $detalle)
                                @php
                                    $codigo = $detalle->producto_codigo
                                        ?? $detalle->producto?->codigo
                                        ?? 'Sin SKU';

                                    $valor = old(
                                        'conteos.'.$detalle->id,
                                        $detalle->conteoFisico?->cajas_contadas
                                    );

                                    $valor = is_scalar($valor) ? (string) $valor : '';

                                    $valido = $valor !== ''
                                        && ctype_digit($valor)
                                        && (float) $valor <= 4294967295;

                                    $diferencia = $valido
                                        ? (int) $valor - $detalle->cajas_facturadas
                                        : null;

                                    $color = $diferencia === null
                                        ? 'secondary'
                                        : ($diferencia < 0
                                            ? 'danger'
                                            : ($diferencia > 0 ? 'warning' : 'success'));

                                    $resultado = $diferencia === null
                                        ? 'Sin contar'
                                        : ($diferencia < 0
                                            ? $diferencia.' (Faltante)'
                                            : ($diferencia > 0
                                                ? '+'.$diferencia.' (Sobrante)'
                                                : '0 (Coincide)'));

                                    // Lo que el cierre sumará al stock, según el conteo guardado.
                                    $danadas = $detalle->mermas->sum('cantidad');
                                    $unidadesAStock = $detalle->conteoFisico && $detalle->producto
                                        ? max(0, $detalle->conteoFisico->cajas_contadas - $danadas) * $detalle->producto->unidades_por_caja
                                        : null;
                                @endphp

                                <tr data-conteo-fila
                                    data-facturadas="{{ $detalle->cajas_facturadas }}">

                                    <th scope="row">
                                        <span class="badge-sku">{{ $codigo }}</span>
                                    </th>

                                    <td>
                                        @if($detalle->producto)
                                            {{ $detalle->producto->nombre }}

                                            <small class="d-block text-muted">
                                                {{ $detalle->producto->categoria?->nombre ?? 'Sin categoría' }}
                                            </small>
                                        @else
                                            <span class="text-warning">
                                                Pendiente de registro en Inventario
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <strong>{{ $detalle->cajas_facturadas }}</strong>
                                    </td>

                                    <td style="min-width: 9rem;">
                                        <label class="visually-hidden"
                                               for="conteo-{{ $detalle->id }}">
                                            Cajas contadas de {{ $codigo }}
                                        </label>

                                        <input type="number"
                                               id="conteo-{{ $detalle->id }}"
                                               name="conteos[{{ $detalle->id }}]"
                                               class="form-control @error('conteos.'.$detalle->id) is-invalid @enderror"
                                               value="{{ $valor }}"
                                               min="0"
                                               max="4294967295"
                                               step="1"
                                               inputmode="numeric"
                                               placeholder="Sin contar"
                                               required
                                               @disabled(!$editable)
                                               @error('conteos.'.$detalle->id)
                                                   aria-invalid="true"
                                                   aria-describedby="error-conteo-{{ $detalle->id }}"
                                               @enderror>

                                        @error('conteos.'.$detalle->id)
                                            <div class="invalid-feedback"
                                                 id="error-conteo-{{ $detalle->id }}">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </td>

                                    <td>
                                        <output for="conteo-{{ $detalle->id }}"
                                                class="badge text-bg-{{ $color }}"
                                                data-conteo-diferencia
                                                aria-live="polite">
                                            {{ $resultado }}
                                        </output>
                                    </td>

                                    <td>{{ $danadas }}</td>

                                    <td>
                                        @if($unidadesAStock !== null)
                                            {{ $unidadesAStock }}
                                        @elseif(!$detalle->producto)
                                            <small class="text-warning">Falta registrar el producto</small>
                                        @else
                                            <small class="text-muted">Falta el conteo</small>
                                        @endif
                                    </td>

                                    @if($editable)
                                        <td>
                                            {{-- La merma se registra sobre lo recibido, por eso exige el conteo. --}}
                                            @if($detalle->conteoFisico)
                                                <a href="{{ route('mermas.create', ['importacion_detalle_id' => $detalle->id]) }}"
                                                   class="btn btn-sm btn-rojo">
                                                    Registrar merma
                                                </a>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="small text-muted">
                    Rojo: faltante. Amarillo: sobrante. Verde: coincide con la factura.
                </p>

                @if($editable)
                    <button type="submit" class="btn btn-rojo">
                        Guardar conteo físico
                    </button>
                @endif
            </form>

            @if($editable)
                <hr>

                <h6>Cerrar recepción</h6>

                <p class="text-muted small">
                    Al cerrar, cada SKU suma al stock (Cajas Contadas - Cajas Dañadas) × unidades por caja.
                    Después ya no se podrá modificar el conteo ni las mermas de esta importación.
                    Guarda el conteo antes de cerrar.
                </p>

                @error('cierre')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <form action="{{ route('importaciones.cerrar', $importacion) }}"
                      method="POST"
                      onsubmit="return confirm('¿Cerrar la recepción y actualizar el stock? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('PATCH')

                    <button type="submit" class="btn btn-negro">
                        Cerrar recepción y actualizar stock
                    </button>
                </form>
            @else
                <p class="alert alert-success mt-3 mb-0">
                    Recepción cerrada: el stock ya fue actualizado con las cajas en buen estado.
                </p>
            @endif
        @else
            <p class="text-center text-muted">Sin detalle de mercadería.</p>
        @endif
    </div>
</div>
