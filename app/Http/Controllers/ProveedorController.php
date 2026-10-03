<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::orderBy('razon_social')->paginate(15);
        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        // Un checkbox sin marcar no se envía, por eso se lee como booleano
        $validated['activo'] = $request->boolean('activo');

        Proveedor::create($validated);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate($this->reglas());

        $validated['activo'] = $request->boolean('activo');

        $proveedor->update($validated);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->importaciones()->exists()) {
            return redirect()->route('proveedores.index')->with('error', 'No se puede eliminar un proveedor que tiene importaciones registradas.');
        }

        if ($proveedor->productos()->exists()) {
            return redirect()->route('proveedores.index')->with('error', 'No se puede eliminar un proveedor que tiene productos asociados. Puede desactivarlo.');
        }

        $proveedor->delete();
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado correctamente.');
    }

    private function reglas(): array
    {
        return [
            'razon_social' => 'required|string|max:150',
            'identificador_wechat' => 'required|string|max:100',
            'correo_electronico' => 'required|email|max:150',
            'pais_origen' => 'required|string|max:100',
            'activo' => 'boolean',
        ];
    }
}
