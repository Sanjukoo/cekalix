<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoProducto extends Model
{
    protected $table = 'atributos_productos';

    protected $fillable = [
        'producto_id',
        'clave',
        'valor',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
