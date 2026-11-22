<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar', ''));

        $clientes = Cliente::withCount('ventas')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('numero_documento', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'buscar'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $cliente = Cliente::create($validated);

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente «{$cliente->nombre}» registrado correctamente.")
            ->with('detalle', $this->detalle($cliente));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente «{$cliente->nombre}» actualizado correctamente.")
            ->with('detalle', $this->detalle($cliente) + [
                'Campos modificados' => $this->camposModificados(array_keys($cliente->getChanges()), [
                    'nombre' => 'Nombre',
                    'tipo_documento' => 'Tipo de documento',
                    'numero_documento' => 'N.º de documento',
                    'telefono' => 'Teléfono',
                    'direccion' => 'Dirección',
                ]),
            ]);
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->ventas()->exists()) {
            return redirect()
                ->route('clientes.index')
                ->with('error', 'No se puede eliminar un cliente que tiene ventas registradas.');
        }

        $detalle = $this->detalle($cliente);

        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente «{$cliente->nombre}» eliminado correctamente.")
            ->with('detalle', $detalle);
    }

    // Datos del cliente que se muestran como comprobación.
    private function detalle(Cliente $cliente): array
    {
        return [
            'Nombre' => $cliente->nombre,
            'Documento' => trim(($cliente->tipo_documento ?? '').' '.($cliente->numero_documento ?? '')),
            'Teléfono' => $cliente->telefono,
            'Dirección' => $cliente->direccion,
        ];
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo_documento' => 'nullable|in:DNI,RUC,CE,Otro',
            'numero_documento' => [
                'nullable',
                'string',
                'max:20',
                'unique:clientes,numero_documento' . ($cliente ? ',' . $cliente->id : ''),
            ],
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
        ], [
            'numero_documento.unique' => 'Ya existe un cliente con ese número de documento.',
        ]);
    }
}
