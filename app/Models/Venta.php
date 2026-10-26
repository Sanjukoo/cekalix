<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    public const VIGENTE = 'vigente';
    public const ANULADA = 'anulada';

    protected $fillable = [
        'cliente_id',
        'fecha_venta',
        'total',
        'estado',
        'registrado_por',
        'motivo_anulacion',
        'anulado_por',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'total' => 'decimal:2',
    ];

    // Número que se muestra en el comprobante y en los listados.
    protected function numero(): Attribute
    {
        return Attribute::get(
            fn () => 'VTA-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT)
        );
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('estado', self::VIGENTE);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ANULADA;
    }

    public function nombreCliente(): string
    {
        return $this->cliente?->nombre ?? 'Cliente general';
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }
}
