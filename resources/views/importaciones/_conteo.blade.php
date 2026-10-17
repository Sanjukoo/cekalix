<div class="card card-custom mt-3">
    <div class="card-header">Conteo físico en descarga</div>
    <div class="card-body">
        @if($importacion->detalles->isNotEmpty())
            <p class="text-muted" id="ayuda-conteo">
                Registra las cajas recibidas para cada SKU; usa 0 si no llegó ninguna.
                Diferencia = Cajas Contadas - Cajas Facturadas.
            </p>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>No se pudo guardar el conteo.</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $editable = $importacion->estado === 'En recepción';
            @endphp
            @if(!$editable)
                <p class="alert alert-info">El conteo solo puede editarse mientras la importación está en recepción.</p>
            @endif

            <form action="{{ route('importaciones.conteo.update', $importacion) }}" method="POST" aria-describedby="ayuda-conteo">
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
                                @if(auth()->user()->role === 'admin')
                                    <th scope="col">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importacion->detalles as $detalle)
                                @php
                                    $valor = old('conteos.'.$detalle->id, $detalle->conteoFisico?->cajas_contadas);
                                    $valor = is_scalar($valor) ? (string) $valor : '';
                                    $valido = $valor !== '' && ctype_digit($valor) && (float) $valor <= 4294967295;
                                    $diferencia = $valido ? (int) $valor - $detalle->cajas_facturadas : null;
                                    $color = $diferencia === null ? 'secondary' : ($diferencia < 0 ? 'danger' : ($diferencia > 0 ? 'warning' : 'success'));
                                    $resultado = $diferencia === null ? 'Sin contar' : ($diferencia < 0 ? $diferencia.' (Faltante)' : ($diferencia > 0 ? '+'.$diferencia.' (Sobrante)' : '0 (Coincide)'));
                                @endphp
                                <tr data-conteo-fila data-facturadas="{{ $detalle->cajas_facturadas }}">
                                    <th scope="row"><span class="badge-sku">{{ $detalle->producto->codigo }}</span></th>
                                    <td>
                                        {{ $detalle->producto->nombre }}
                                        <small class="d-block text-muted">{{ $detalle->producto->categoria->nombre }}</small>
                                    </td>
                                    <td><strong>{{ $detalle->cajas_facturadas }}</strong></td>
                                    <td style="min-width: 9rem;">
                                        <label class="visually-hidden" for="conteo-{{ $detalle->id }}">Cajas contadas de {{ $detalle->producto->codigo }}</label>
                                        <input type="number" id="conteo-{{ $detalle->id }}" name="conteos[{{ $detalle->id }}]"
                                               class="form-control @error('conteos.'.$detalle->id) is-invalid @enderror"
                                               value="{{ $valor }}" min="0" max="4294967295" step="1" inputmode="numeric"
                                               placeholder="Sin contar" required @disabled(!$editable)
                                               @error('conteos.'.$detalle->id) aria-invalid="true" aria-describedby="error-conteo-{{ $detalle->id }}" @enderror>
                                        @error('conteos.'.$detalle->id)
                                            <div class="invalid-feedback" id="error-conteo-{{ $detalle->id }}">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td><output for="conteo-{{ $detalle->id }}" class="badge text-bg-{{ $color }}" data-conteo-diferencia aria-live="polite">{{ $resultado }}</output></td>
                                    @if(auth()->user()->role === 'admin')
                                        <td><a href="{{ route('mermas.create', ['importacion_detalle_id' => $detalle->id]) }}" class="btn btn-sm btn-rojo">Registrar merma</a></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted">Rojo: faltante. Amarillo: sobrante. Verde: coincide con la factura.</p>
                @if($editable)
                    <button type="submit" class="btn btn-rojo">Guardar conteo físico</button>
                @endif
            </form>
        @else
            <p class="text-center text-muted">Sin detalle de mercadería.</p>
        @endif
    </div>
</div>
