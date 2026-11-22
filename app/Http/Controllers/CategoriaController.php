<?php

namespace App\Http\Controllers;

use App\Models\AtributoCategoria;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            'atributos.*.nombre' => 'required|string|max:100',
            // Opciones de la lista desplegable, separadas por comas (opcional).
            'atributos.*.opciones' => 'nullable|string|max:1000',
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
            'atributos.required' => 'Agrega al menos un atributo.',
            'atributos.*.nombre.required' => 'Escribe el nombre de cada atributo.',
            'atributos.*.opciones.max' => 'La lista de opciones de un atributo es demasiado larga.',
        ]);

        // Dos atributos con el mismo nombre guardarían su valor en la misma clave del producto.
        $campos = collect($validated['atributos'])->map(fn ($atributo) => Str::slug($atributo['nombre']));

        if ($campos->count() !== $campos->unique()->count()) {
            throw ValidationException::withMessages([
                'atributos' => 'No repitas el nombre de un atributo dentro de la misma categoría.',
            ]);
        }

        $slug = Str::slug($validated['nombre']);

        if (Categoria::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una categoría con un nombre equivalente.',
            ]);
        }

        $categoria = DB::transaction(function () use ($validated, $slug) {
            $categoria = Categoria::create([
                'nombre' => $validated['nombre'],
                'slug' => $slug,
                'descripcion' => $validated['descripcion'] ?? null,
            ]);

            foreach ($validated['atributos'] as $atributo) {
                AtributoCategoria::create([
                    'categoria_id' => $categoria->id,
                    'nombre' => trim($atributo['nombre']),
                    'opciones' => AtributoCategoria::opcionesDesdeTexto($atributo['opciones'] ?? null),
                ]);
            }

            return $categoria;
        });

        return redirect()
            ->route('productos.create')
            ->with('success', "Categoría «{$categoria->nombre}» creada correctamente.")
            ->with('detalle', [
                'Nombre' => $categoria->nombre,
                'Descripción' => $categoria->descripcion,
                'Atributos ('.count($validated['atributos']).')' => collect($validated['atributos'])
                    ->pluck('nombre')
                    ->map(fn ($nombre) => trim($nombre))
                    ->implode(', '),
            ]);
    }
}
