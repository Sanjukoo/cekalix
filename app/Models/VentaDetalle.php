<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    // Modalidad de venta de una línea. Por unidad no guarda cajas (cajas = null).
    public const POR_CAJA = 'caja';
    public const POR_UNIDAD = 'unidad';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'cajas',
        'unidades_por_caja',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cajas' => 'integer',
        'unidades_por_caja' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function modalidad(): string
    {
        return $this->cajas !== null ? self::POR_CAJA : self::POR_UNIDAD;
    }

    public function esPorCaja(): bool
    {
        return $this->modalidad() === self::POR_CAJA;
    }

    // Texto de la modalidad: "Por caja (10 u.)" o "Por unidad".
    public function descripcionModalidad(): string
    {
        return $this->esPorCaja()
            ? 'Por caja ('.$this->unidades_por_caja.' u.)'
            : 'Por unidad';
    }

    // Cantidad tal como se vendió: "3 cajas" o "25 unidades".
    public function descripcionCantidad(): string
    {
        return $this->esPorCaja()
            ? $this->cajas.' '.($this->cajas === 1 ? 'caja' : 'cajas')
            : $this->cantidad.' '.($this->cantidad === 1 ? 'unidad' : 'unidades');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
