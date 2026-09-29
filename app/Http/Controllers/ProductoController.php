<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\AtributoProducto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    const ATRIBUTOS_POR_CATEGORIA = [
        'correderas' => ['longitud', 'espesor', 'ancho', 'color'],
        'bisagras' => ['tipo', 'acabado', 'peso'],
        'pistones' => ['fuerza', 'longitud', 'acabado'],
        'cerraduras' => ['material', 'tamaño'],
    ];

    public function index()
    {
        $productos = Producto::with('categoria')->paginate(15);
        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = Categoria::with('atributos')
          ->orderBy('nombre')
          ->get();
        return view('productos.create', compact('categorias'));
        }

    public function store(Request $request)
    {
    $validated = $request->validate([
        'codigo' => 'required|string|unique:productos,codigo|max:50',
        'nombre' => 'required|string|max:100',
        'categoria_id' => 'required|exists:categorias,id',
        'proveedor' => 'required|string|max:100',
        'stock' => 'required|integer|min:0',
        'unidades_por_caja' => 'required|integer|min:1',
        'descripcion' => 'nullable|string',
    ]);

    // Crear el producto
    $producto = Producto::create($validated);

    // Obtener la categoría con sus atributos
    $categoria = Categoria::with('atributos')
        ->find($validated['categoria_id']);

    // Guardar los atributos personalizados
    foreach ($categoria->atributos as $atributo) {

        $campo = \Illuminate\Support\Str::slug($atributo->nombre);

        $valor = $request->input("atributo_$campo");

        if ($valor !== null && $valor !== '') {

            AtributoProducto::create([
                'producto_id' => $producto->id,
                'clave' => $campo,
                'valor' => $valor,
            ]);
        }
    }

    return redirect()
        ->route('productos.index')
        ->with('success', 'Producto registrado correctamente.');
    }

    public function show(Producto $producto)
    {
        $producto->load('categoria', 'atributos');
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $atributos = $producto->obtenerAtributos();
        return view('productos.edit', compact('producto', 'categorias', 'atributos'));
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:productos,codigo,' . $producto->id . '|max:50',
            'nombre' => 'required|string|max:100',
            'categoria_id' => 'required|exists:categorias,id',
            'proveedor' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'unidades_por_caja' => 'required|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        $producto->update($validated);

        // Actualizar atributos
        $categoria = Categoria::find($validated['categoria_id']);
        $atributosEsperados = self::ATRIBUTOS_POR_CATEGORIA[$categoria->slug] ?? [];

        // Eliminar atributos anteriores
        $producto->atributos()->delete();

        // Guardar nuevos atributos
        foreach ($atributosEsperados as $atributo) {
            $valor = $request->input("atributo_$atributo");
            if ($valor) {
                AtributoProducto::create([
                    'producto_id' => $producto->id,
                    'clave' => $atributo,
                    'valor' => $valor,
                ]);
            }
        }

        return redirect()->route('productos.show', $producto)->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function listarAjax()
    {
        $productos = Producto::with('categoria', 'atributos')->get();
        return view('productos.tabla', compact('productos'));
    }
}
