<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Importacion;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {   
        //Obtener el rol del usuario que inicio sesión
        $role=$request->user()->role;

        //Acceso al dashboard según el rol iniciado
        switch($role){
            case 'admin':
                $totalProveedores=Proveedor::activos()->count();
                $totalImportaciones= Importacion::count();
                return view('dashboard.admin', compact('totalProveedores', 'totalImportaciones'));
            case 'inventario':
                $totalProductos=Producto::count();
                $productosBajoStock=Producto::where('stock','<',10)->count();
                return view('dashboard.inventario', compact('totalProductos','productosBajoStock'));

            case 'ventas':
                $totalProductos=Producto::count();
                return view('dashboard.ventas', compact('totalProductos'));
            default:
                abort(403, 'Tu usuario no tiene un rol autorizado');
        }
    }
}
