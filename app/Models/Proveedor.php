<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function importaciones()
    {
        return $this->hasMany(Importacion::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
