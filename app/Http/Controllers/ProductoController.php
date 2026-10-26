<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ImportacionDetalle;
use App\Models\MovimientoInventario;
use App\Models\Categoria;
use App\Models\AtributoProducto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    public function index(Request $request)
   {
        $buscar = trim($request->input('buscar', ''));

        $productos = Producto::with('categoria', 'proveedor')
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
            })
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', compact('productos', 'buscar'));
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

        return view('productos.create', compact(
          'categorias',
          'detallesPendientes'
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

       DB::transaction(function () use ($request, $validated) {
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
        $this->guardarAtributos($request, $producto);
    });

    return redirect()
        ->route('productos.index')
        ->with('success', 'Producto registrado o actualizado correctamente.');
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
        return view('productos.edit', compact('producto', 'categorias', 'proveedores', 'atributos'));
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

        DB::transaction(function () use ($request, $validated, $producto) {
            $producto->update($validated);

            // Reemplazar los atributos anteriores por los de la categoría actual
            $producto->atributos()->delete();
            $this->guardarAtributos($request, $producto);
        });

        return redirect()->route('productos.show', $producto)->with('success', 'Producto actualizado correctamente.');
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

        DB::transaction(function () use ($validated, $producto) {
            $productoActual = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

            MovimientoInventario::registrar(
                $productoActual,
                $validated['cantidad'],
                'ajuste',
                $validated['motivo']
            );
        });

        return redirect()
            ->route('productos.show', $producto)
            ->with('success', 'Ajuste de stock registrado en el kárdex.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->importacionDetalles()->exists() || $producto->ventaDetalles()->exists()) {
            return redirect()->route('productos.index')->with('error', 'No se puede eliminar un producto que tiene importaciones o ventas registradas.');
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    // Guarda los atributos que define la categoría del producto
    
   private function guardarAtributos(Request $request, Producto $producto): void
  {
       $categoria = Categoria::with('atributos')
           ->find($producto->categoria_id);

       foreach ($categoria->atributos as $atributo) {
           $campo = Str::slug($atributo->nombre);
           $valor = $request->input("atributo_$campo");

           if ($valor !== null && $valor !== '') {
               $producto->atributos()->updateOrCreate(
                   ['clave' => $campo],
                   ['valor' => $valor]
              );
           }
       }
    }

    public function listarAjax()
    {
        $productos = Producto::with('categoria', 'proveedor', 'atributos')->get();
        return view('productos.tabla', compact('productos'));
    }
}
