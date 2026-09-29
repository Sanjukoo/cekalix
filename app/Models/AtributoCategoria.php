<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtributoCategoria extends Model
{
    protected $table = 'atributos_categoria';

    protected $fillable = [
        'categoria_id',
        'nombre',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}