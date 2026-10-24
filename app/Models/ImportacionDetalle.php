<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionDetalle extends Model
{
    protected $table = 'importacion_detalles';

    protected $fillable = [
        'importacion_id',
        'producto_id',
        'producto_codigo',
        'cajas_facturadas',
    ];

    protected $casts = [
        'producto_id' => 'integer',
        'cajas_facturadas' => 'integer',
    ];

    public function importacion()
    {
        return $this->belongsTo(Importacion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function conteoFisico()
    {
        return $this->hasOne(ConteoFisico::class);
    }

    public function mermas()
    {
        return $this->hasMany(Merma::class, 'importacion_detalle_id');
    }
}