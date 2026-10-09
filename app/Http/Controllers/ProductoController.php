<?php

namespace App\Http\Controllers;

use App\Models\Producto;
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
        $proveedores = Proveedor::activos()->orderBy('razon_social')->get();
        return view('productos.create', compact('categorias', 'proveedores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo|max:50',
            'nombre' => 'required|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor_id' => 'required|exists:proveedores,id',
            'stock' => 'required|integer|min:0',
            'unidades_por_caja' => 'required|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $producto = Producto::create($validated);
            $this->guardarAtributos($request, $producto);
        });

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto registrado correctamente.');
    }

    public function show(Producto $producto)
    {
        $producto->load('categoria', 'proveedor', 'atributos');
        return view('productos.show', compact('producto'));
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
            'stock' => 'required|integer|min:0',
            'unidades_por_caja' => 'required|integer|min:1',
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

    public function destroy(Producto $producto)
    {
        if ($producto->importacionDetalles()->exists()) {
            return redirect()->route('productos.index')->with('error', 'No se puede eliminar un producto que tiene importaciones registradas.');
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    // Guarda los atributos que define la categoría del producto
    private function guardarAtributos(Request $request, Producto $producto): void
    {
        $categoria = Categoria::with('atributos')->find($producto->categoria_id);

        foreach ($categoria->atributos as $atributo) {
            $campo = Str::slug($atributo->nombre);
            $valor = $request->input("atributo_$campo");

            if ($valor !== null && $valor !== '') {
                AtributoProducto::create([
                    'producto_id' => $producto->id,
                    'clave' => $campo,
                    'valor' => $valor,
                ]);
            }
        }
    }

    public function listarAjax()
    {
        $productos = Producto::with('categoria', 'proveedor', 'atributos')->get();
        return view('productos.tabla', compact('productos'));
    }
}
