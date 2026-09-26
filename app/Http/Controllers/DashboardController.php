<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Importacion;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProductos = Producto::count();
        $totalProveedores = Proveedor::activos()->count();
        $totalImportaciones = Importacion::count();
        $productosBajoStock = Producto::where('stock', '<', 10)->count();

        return view('dashboard.index', compact(
            'totalProductos',
            'totalProveedores',
            'totalImportaciones',
            'productosBajoStock'
        ));
    }
}
