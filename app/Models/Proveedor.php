<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social',
        'identificador_wechat',
        'correo_electronico',
        'pais_origen',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function importaciones()
    {
        return $this->hasMany(Importacion::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
