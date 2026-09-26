<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = [
        'proveedor_id',
        'numero_factura',
        'numero_contenedor',
        'fecha_llegada',
        'estado',
    ];

    protected $casts = [
        'fecha_llegada' => 'date',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(ImportacionDetalle::class);
    }
}
