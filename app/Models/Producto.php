<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_id',
        'proveedor_id',
        'stock',
        'unidades_por_caja',
        'precio_venta',
        'descripcion',
    ];

    protected $casts = [
        'stock' => 'integer',
        'unidades_por_caja' => 'integer',
        'precio_venta' => 'decimal:2',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function atributos()
    {
        return $this->hasMany(AtributoProducto::class);
    }

    public function importacionDetalles()
    {
        return $this->hasMany(ImportacionDetalle::class);
    }

    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function obtenerAtributos(): array
    {
        return $this->atributos()
            ->pluck('valor', 'clave')
            ->toArray();
    }
}
