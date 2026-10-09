<?php

namespace App\Http\Controllers;

use App\Models\Merma;
use App\Models\Importacion;
use App\Models\ImportacionDetalle;
use Illuminate\Http\Request;

class MermaController extends Controller
{
    public function index()
    {
        $mermas = Merma::with(
            'detalleImportacion.importacion.proveedor',
            'detalleImportacion.producto'
        )
            ->orderBy('id', 'desc')
            ->paginate(15);

        $totalCajasDaniadas = Merma::sum('cantidad');

        return view('mermas.index', compact(
            'mermas',
            'totalCajasDaniadas'
        ));
    }

    public function create()
    {
        $importaciones = Importacion::with('proveedor', 'detalles.producto')
            ->orderBy('id', 'desc')
            ->get();

        return view('mermas.create', compact('importaciones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'importacion_detalle_id' => 'required|exists:importacion_detalles,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'fecha_registro' => 'required|date',
        ]);

        $detalle = ImportacionDetalle::with('mermas')
            ->findOrFail($validated['importacion_detalle_id']);

        $totalRegistrado = $detalle->mermas->sum('cantidad');
        $totalNuevo = $totalRegistrado + $validated['cantidad'];

        if ($totalNuevo > $detalle->cajas_facturadas) {
            return back()
                ->withInput()
                ->withErrors([
                    'cantidad' => 'La cantidad dañada supera las cajas facturadas de esta importación.',
                ]);
        }

        Merma::create($validated);

        return redirect()
            ->route('mermas.index')
            ->with('success', 'Merma registrada correctamente.');
    }
}