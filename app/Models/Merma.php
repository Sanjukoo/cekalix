<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merma extends Model
{
    protected $table = 'mermas';

    protected $fillable = [
        'importacion_detalle_id',
        'cantidad',
        'motivo',
        'fecha_registro',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha_registro' => 'date',
    ];

    public function detalleImportacion()
    {
        return $this->belongsTo(
            ImportacionDetalle::class,
            'importacion_detalle_id'
        );
    }
}