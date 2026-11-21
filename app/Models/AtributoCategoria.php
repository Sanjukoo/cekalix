<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AtributoCategoria extends Model
{
    protected $table = 'atributos_categoria';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'opciones',
    ];

    protected $casts = [
        // Valores permitidos del atributo; vacío = texto libre.
        'opciones' => 'array',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Clave con la que se guarda el valor en atributos_productos (ej. "Tamaño" -> "tamano").
    public function campo(): string
    {
        return Str::slug($this->nombre);
    }

    public function tieneOpciones(): bool
    {
        return !empty($this->opciones);
    }

    // Convierte "Rojo, Azul , ,Verde" en ["Rojo", "Azul", "Verde"] (sin vacíos ni repetidos).
    public static function opcionesDesdeTexto(?string $texto): ?array
    {
        $opciones = collect(explode(',', (string) $texto))
            ->map(fn ($opcion) => trim($opcion))
            ->filter(fn ($opcion) => $opcion !== '')
            ->unique(fn ($opcion) => mb_strtolower($opcion))
            ->values()
            ->all();

        return $opciones ?: null;
    }
}
