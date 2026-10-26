<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public const TIPOS = [
        'saldo_inicial' => 'Saldo inicial',
        'cierre_importacion' => 'Recepción de importación',
        'venta' => 'Venta',
        'anulacion_venta' => 'Anulación de venta',
        'edicion_venta' => 'Edición de venta',
        'ajuste' => 'Ajuste manual',
    ];

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'saldo',
        'motivo',
        'referencia_tipo',
        'referencia_id',
        'registrado_por',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'saldo' => 'integer',
        'referencia_id' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Único punto donde cambia el stock de un producto.
     * Debe llamarse dentro de una transacción que ya bloqueó el producto.
     */
    public static function registrar(
        Producto $producto,
        int $cantidad,
        string $tipo,
        ?string $motivo = null,
        ?Model $referencia = null
    ): ?self {
        if ($cantidad === 0) {
            return null;
        }

        $saldo = $producto->stock + $cantidad;

        if ($saldo < 0) {
            throw ValidationException::withMessages([
                'stock' => "Stock insuficiente para {$producto->codigo}: hay {$producto->stock} unidades disponibles.",
            ]);
        }

        $producto->update(['stock' => $saldo]);

        return static::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'saldo' => $saldo,
            'motivo' => $motivo,
            'referencia_tipo' => $referencia ? class_basename($referencia) : null,
            'referencia_id' => $referencia?->getKey(),
            'registrado_por' => auth()->id(),
        ]);
    }
}
