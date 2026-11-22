<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ImportacionDetalle;
use App\Models\MovimientoInventario;
use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index(Request $request)
   {
        $buscar = trim((string) $request->input('buscar', ''));

        $categorias = Categoria::orderBy('nombre')->get();

        // Solo se filtra por una categoría que exista; cualquier otro valor muestra todas.
        $categoriaActual = $categorias->firstWhere('id', (int) $request->input('categoria'));

        // Productos que coinciden con la búsqueda (sin filtrar aún por categoría).
        $coincidencias = Producto::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas('atributos', function ($atributos) use ($buscar) {
                            $atributos->where('clave', 'like', "%{$buscar}%")
                                ->orWhere('valor', 'like', "%{$buscar}%");
                        });
                });
            });

        // Cantidad de productos por categoría para los botones de filtro.
        $conteoPorCategoria = (clone $coincidencias)
            ->selectRaw('categoria_id, COUNT(*) as total')
            ->groupBy('categoria_id')
            ->pluck('total', 'categoria_id');

        $productos = (clone $coincidencias)
            ->with('categoria', 'proveedor')
            ->when($categoriaActual, fn ($query) => $query->where('categoria_id', $categoriaActual->id))
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', compact(
            'productos',
            'buscar',
            'categorias',
            'categoriaActual',
            'conteoPorCategoria'
        ));
    }
    
    public function create()
   {
       $categorias = Categoria::with('atributos')
           ->orderBy('nombre')
           ->get();

       $detallesPendientes = ImportacionDetalle::with([
           'importacion.proveedor',
           'producto.categoria',
           'producto.atributos',
       ])
           ->whereNotNull('producto_codigo')
           ->whereHas('importacion', function ($query) {
               $query->where('estado', 'En recepción');
           })
           ->where(function ($query) {
               $query->whereNull('producto_id')
                   ->orWhereNotNull('producto_id');
           })
           ->orderByDesc('id')
           ->get();

        $atributosPorCategoria = $this->atributosPorCategoria($categorias);

        return view('productos.create', compact(
          'categorias',
          'detallesPendientes',
          'atributosPorCategoria'
       ));
   }

    
   public function store(Request $request)
   {
       $validated = $request->validate([
           'importacion_detalle_id' => 'required|integer|exists:importacion_detalles,id',
           'nombre' => 'required|string|max:100',
           'categoria_id' => 'required|exists:categorias,id',
           'unidades_por_caja' => 'required|integer|min:1',
           'precio_venta' => 'nullable|numeric|min:0',
           'descripcion' => 'nullable|string',
       ]);

       [$producto, $esNuevo] = DB::transaction(function () use ($request, $validated) {
           $detalle = ImportacionDetalle::with('importacion')
               ->whereKey($validated['importacion_detalle_id'])
               ->lockForUpdate()
               ->firstOrFail();

           if ($detalle->importacion->estado !== 'En recepción') {
               throw \Illuminate\Validation\ValidationException::withMessages([
                   'importacion_detalle_id' =>
                      'La importación ya no está pendiente de recepción.',
               ]);
           }

           $codigo = trim($detalle->producto_codigo);

        // Buscar el producto vinculado o uno que ya exista con el mismo SKU.
           $producto = $detalle->producto_id
               ? Producto::whereKey($detalle->producto_id)->lockForUpdate()->first()
               : Producto::where('codigo', $codigo)->lockForUpdate()->first();

           $esNuevo = !$producto;

           if ($producto) {
            // Reutilizar el producto existente y conservar su stock.
               $producto->update([
                   'nombre' => $validated['nombre'],
                   'categoria_id' => $validated['categoria_id'],
                   'unidades_por_caja' => $validated['unidades_por_caja'],
                   'precio_venta' => $validated['precio_venta'] ?? $producto->precio_venta,
                   'descripcion' => $validated['descripcion'] ?? null,
              ]);

               $detalle->producto_id = $producto->id;
               $detalle->save();
           } else {
            // Crear un producto únicamente si el SKU todavía no existe.
            // Nace sin stock: lo recibe al cerrar la recepción de la importación.
               $producto = Producto::create([
                   'codigo' => $codigo,
                   'nombre' => $validated['nombre'],
                   'categoria_id' => $validated['categoria_id'],
                   'proveedor_id' => $detalle->importacion->proveedor_id,
                   'stock' => 0,
                   'unidades_por_caja' => $validated['unidades_por_caja'],
                   'precio_venta' => $validated['precio_venta'] ?? 0,
                   'descripcion' => $validated['descripcion'] ?? null,
               ]);

               $detalle->producto_id = $producto->id;
               $detalle->save();
        }

        // Vincular otros detalles pendientes del mismo SKU.
        ImportacionDetalle::whereNull('producto_id')
            ->where('producto_codigo', $codigo)
            ->update(['producto_id' => $producto->id]);

        // Guardar o actualizar los atributos sin duplicar sus claves.
        $this->guardarAtributos($request, $producto, $producto->obtenerAtributos());

        return [$producto, $esNuevo];
    });

    $accion = $esNuevo ? 'registrado' : 'actualizado (el SKU ya existía)';

    return redirect()
        ->route('productos.index')
        ->with('success', "Producto {$producto->codigo} {$accion} correctamente.")
        ->with('detalle', $this->detalleProducto($producto));
   }



    public function show(Producto $producto)
    {
        $producto->load('categoria', 'proveedor', 'atributos');

        // Historial del kárdex que se muestra debajo de la ficha del producto.
        $movimientos = $producto->movimientos()
            ->with('usuario')
            ->latest('id')
            ->take(50)
            ->get();

        return view('productos.show', compact('producto', 'movimientos'));
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::with('atributos')
            ->orderBy('nombre')
            ->get();
        // Activos, más el actual aunque esté inactivo, para no perderlo al editar
        $proveedores = Proveedor::activos()
            ->orWhere('id', $producto->proveedor_id)
            ->orderBy('razon_social')
            ->get();
        $atributos = $producto->obtenerAtributos();
        $atributosPorCategoria = $this->atributosPorCategoria($categorias);

        return view('productos.edit', compact(
            'producto',
            'categorias',
            'proveedores',
            'atributos',
            'atributosPorCategoria'
        ));
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo,' . $producto->id . '|max:50',
            'nombre' => 'required|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'unidades_por_caja' => 'required|integer|min:1',
            'precio_venta' => 'nullable|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        $cambios = DB::transaction(function () use ($request, $validated, $producto) {
            $valoresAnteriores = $producto->obtenerAtributos();

            $producto->update($validated);
            $cambios = array_keys($producto->getChanges());

            // Reemplazar los atributos anteriores por los de la categoría actual
            $producto->atributos()->delete();
            $this->guardarAtributos($request, $producto, $valoresAnteriores);

            if ($valoresAnteriores != $producto->obtenerAtributos()) {
                $cambios[] = 'atributos';
            }

            return $cambios;
        });

        return redirect()->route('productos.show', $producto)
            ->with('success', "Producto {$producto->codigo} actualizado correctamente.")
            ->with('detalle', $this->detalleProducto($producto) + [
                'Campos modificados' => $this->camposModificados($cambios, [
                    'codigo' => 'Código',
                    'nombre' => 'Nombre',
                    'categoria_id' => 'Categoría',
                    'proveedor_id' => 'Proveedor',
                    'unidades_por_caja' => 'Unidades por caja',
                    'precio_venta' => 'Precio de venta',
                    'descripcion' => 'Descripción',
                    'atributos' => 'Atributos',
                ]),
            ]);
    }

    // Ajuste manual de stock (conteo físico, merma no registrada, etc.). Siempre queda en el kárdex con su motivo.
    public function ajustar(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|not_in:0|between:-100000,100000',
            'motivo' => 'required|string|max:255',
        ], [
            'cantidad.not_in' => 'El ajuste no puede ser 0.',
        ]);

        [$stockAnterior, $stockNuevo] = DB::transaction(function () use ($validated, $producto) {
            $productoActual = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $stockAnterior = $productoActual->stock;

            MovimientoInventario::registrar(
                $productoActual,
                $validated['cantidad'],
                'ajuste',
                $validated['motivo']
            );

            return [$stockAnterior, $productoActual->stock];
        });

        $cantidad = $validated['cantidad'];

        return redirect()
            ->route('productos.show', $producto)
            ->with('success', "Ajuste de stock de {$producto->codigo} registrado en el kárdex.")
            ->with('detalle', [
                'Producto' => "{$producto->codigo} - {$producto->nombre}",
                'Ajuste' => ($cantidad > 0 ? '+' : '').$cantidad.' unidades',
                'Stock anterior' => $stockAnterior.' unidades',
                'Stock actual' => $stockNuevo.' unidades',
                'Motivo' => $validated['motivo'],
            ]);
    }

    public function destroy(Producto $producto)
    {
        if ($producto->importacionDetalles()->exists() || $producto->ventaDetalles()->exists()) {
            return redirect()->route('productos.index')->with('error', "No se puede eliminar el producto {$producto->codigo} porque tiene importaciones o ventas registradas.");
        }

        $detalle = $this->detalleProducto($producto);

        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', "Producto {$producto->codigo} eliminado correctamente.")
            ->with('detalle', $detalle);
    }

    // Datos del producto que se muestran como comprobación.
    private function detalleProducto(Producto $producto): array
    {
        $producto->loadMissing('categoria', 'proveedor');

        return [
            'Código (SKU)' => $producto->codigo,
            'Nombre' => $producto->nombre,
            'Categoría' => $producto->categoria?->nombre,
            'Proveedor' => $producto->proveedor?->razon_social,
            'Unidades por caja' => $producto->unidades_por_caja,
            'Precio de venta' => $this->soles($producto->precio_venta),
            'Stock' => $producto->stock.' unidades',
        ];
    }

    /*
     * Guarda los atributos que define la categoría del producto.
     * Si el atributo tiene lista de opciones, el valor debe ser una de ellas;
     * se acepta también el valor que el producto ya tenía, para no perderlo.
     */
    private function guardarAtributos(Request $request, Producto $producto, array $valoresAnteriores = []): void
    {
        $categoria = Categoria::with('atributos')->find($producto->categoria_id);
        $errores = [];

        foreach ($categoria->atributos as $atributo) {
            $campo = $atributo->campo();
            $valor = trim((string) $request->input("atributo_$campo", ''));

            if ($valor === '') {
                continue;
            }

            if (mb_strlen($valor) > 255) {
                $errores["atributo_$campo"] = "El valor de {$atributo->nombre} es demasiado largo.";
                continue;
            }

            $permitidos = array_merge(
                $atributo->opciones ?? [],
                isset($valoresAnteriores[$campo]) ? [$valoresAnteriores[$campo]] : []
            );

            if ($atributo->tieneOpciones() && !in_array($valor, $permitidos, true)) {
                $errores["atributo_$campo"] = "Elige un valor de la lista para {$atributo->nombre}.";
                continue;
            }

            $producto->atributos()->updateOrCreate(
                ['clave' => $campo],
                ['valor' => $valor]
            );
        }

        if ($errores) {
            // Se lanza dentro de la transacción, así que no queda nada guardado a medias.
            throw \Illuminate\Validation\ValidationException::withMessages($errores);
        }
    }

    // Atributos de cada categoría (por slug) para dibujar los campos del formulario.
    private function atributosPorCategoria($categorias): array
    {
        return $categorias->mapWithKeys(fn (Categoria $categoria) => [
            $categoria->slug => $categoria->atributos->map(fn ($atributo) => [
                'nombre' => $atributo->nombre,
                'campo' => $atributo->campo(),
                'opciones' => $atributo->opciones ?? [],
            ])->values()->all(),
        ])->all();
    }

    public function listarAjax()
    {
        $productos = Producto::with('categoria', 'proveedor', 'atributos')->get();
        return view('productos.tabla', compact('productos'));
    }
}
