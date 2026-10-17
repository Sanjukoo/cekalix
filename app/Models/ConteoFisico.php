<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConteoFisico extends Model
{
    protected $table = 'conteos_fisicos';

    protected $fillable = [
        'importacion_detalle_id',
        'cajas_contadas',
        'diferencia',
        'registrado_por',
    ];

    protected $casts = [
        'cajas_contadas' => 'integer',
        'diferencia' => 'integer',
    ];

    public function detalle()
    {
        return $this->belongsTo(ImportacionDetalle::class, 'importacion_detalle_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
