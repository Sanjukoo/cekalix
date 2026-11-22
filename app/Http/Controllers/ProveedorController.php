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

        $proveedor = Proveedor::create($validated);

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$proveedor->razon_social}» registrado correctamente.")
            ->with('detalle', $this->detalle($proveedor));
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

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$proveedor->razon_social}» actualizado correctamente.")
            ->with('detalle', $this->detalle($proveedor) + [
                'Campos modificados' => $this->camposModificados(array_keys($proveedor->getChanges()), [
                    'razon_social' => 'Razón social',
                    'identificador_wechat' => 'WeChat',
                    'correo_electronico' => 'Correo electrónico',
                    'pais_origen' => 'País de origen',
                    'activo' => 'Estado',
                ]),
            ]);
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->importaciones()->exists()) {
            return redirect()->route('proveedores.index')->with('error', 'No se puede eliminar un proveedor que tiene importaciones registradas.');
        }

        if ($proveedor->productos()->exists()) {
            return redirect()->route('proveedores.index')->with('error', 'No se puede eliminar un proveedor que tiene productos asociados. Puede desactivarlo.');
        }

        $detalle = $this->detalle($proveedor);

        $proveedor->delete();

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$proveedor->razon_social}» eliminado correctamente.")
            ->with('detalle', $detalle);
    }

    // Datos del proveedor que se muestran como comprobación.
    private function detalle(Proveedor $proveedor): array
    {
        return [
            'Razón social' => $proveedor->razon_social,
            'WeChat' => $proveedor->identificador_wechat,
            'Correo electrónico' => $proveedor->correo_electronico,
            'País de origen' => $proveedor->pais_origen,
            'Estado' => $proveedor->activo ? 'Activo' : 'Inactivo',
        ];
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
