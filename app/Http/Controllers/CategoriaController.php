<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\AtributoCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    public function create()
    {
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:categorias,nombre',
            'descripcion' => 'nullable|string',
            'atributos' => 'required|array|min:1',
            'atributos.*' => 'required|string|max:100',
        ]);

        $categoria = Categoria::create([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        foreach ($validated['atributos'] as $atributo) {
            AtributoCategoria::create([
                'categoria_id' => $categoria->id,
                'nombre' => $atributo,
            ]);
        }

        return redirect()
            ->route('productos.create')
            ->with('success', 'Categoría creada correctamente.');
    }
}