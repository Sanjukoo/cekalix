<?php

namespace Tests\Feature;

use App\Models\AtributoCategoria;
use App\Models\AtributoProducto;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\ProductoSeeder;
use Database\Seeders\ProveedorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtributosOpcionesTest extends TestCase
{
    use RefreshDatabase;

    private function cerraduraDeEjemplo(): Producto
    {
        $this->seed([CategoriaSeeder::class, ProveedorSeeder::class]);

        $producto = Producto::create([
            'codigo' => 'CER-TEST',
            'nombre' => 'Cerradura de prueba',
            'categoria_id' => Categoria::where('slug', 'cerraduras')->value('id'),
            'proveedor_id' => Proveedor::value('id'),
            'stock' => 0,
            'unidades_por_caja' => 10,
            'precio_venta' => 5,
        ]);

        $producto->atributos()->create(['clave' => 'material', 'valor' => 'Latón']);

        return $producto;
    }

    private function datosEdicion(Producto $producto, array $atributos): array
    {
        return array_merge([
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'categoria_id' => $producto->categoria_id,
            'proveedor_id' => $producto->proveedor_id,
            'unidades_por_caja' => $producto->unidades_por_caja,
            'precio_venta' => $producto->precio_venta,
        ], $atributos);
    }

    public function test_todos_los_atributos_de_ejemplo_tienen_al_menos_tres_opciones(): void
    {
        $this->seed(CategoriaSeeder::class);

        $atributos = AtributoCategoria::with('categoria')->get();
        $this->assertCount(12, $atributos);

        foreach ($atributos as $atributo) {
            $this->assertGreaterThanOrEqual(
                3,
                count($atributo->opciones ?? []),
                "{$atributo->categoria->nombre} / {$atributo->nombre} tiene menos de 3 opciones"
            );
        }
    }

    public function test_los_valores_de_los_productos_de_ejemplo_estan_en_las_listas(): void
    {
        $this->seed([CategoriaSeeder::class, ProveedorSeeder::class, ProductoSeeder::class]);

        foreach (AtributoProducto::with('producto.categoria.atributos')->get() as $valor) {
            $atributo = $valor->producto->categoria->atributos
                ->first(fn ($a) => $a->campo() === $valor->clave);

            $this->assertNotNull($atributo, "La clave {$valor->clave} no pertenece a su categoría");
            $this->assertContains($valor->valor, $atributo->opciones, "{$valor->clave}: {$valor->valor}");
        }
    }

    public function test_la_ficha_del_producto_envia_las_opciones_para_la_lista_desplegable(): void
    {
        $producto = $this->cerraduraDeEjemplo();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $html = $this->get("/productos/{$producto->id}/edit")->assertOk()
            ->assertSee('js/atributos-producto.js', false)
            ->getContent();

        $this->assertStringContainsString('Acero inoxidable', $html);
        $this->assertStringContainsString('opciones', $html);

        $this->get('/productos/create')->assertOk()->assertSee('js/atributos-producto.js', false);
    }

    public function test_guarda_un_valor_de_la_lista(): void
    {
        $producto = $this->cerraduraDeEjemplo();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put("/productos/{$producto->id}", $this->datosEdicion($producto, [
            'atributo_material' => 'Acero inoxidable',
            'atributo_tamano' => '22 mm',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['material' => 'Acero inoxidable', 'tamano' => '22 mm'], $producto->fresh()->obtenerAtributos());
    }

    public function test_rechaza_un_valor_fuera_de_la_lista_sin_perder_los_anteriores(): void
    {
        $producto = $this->cerraduraDeEjemplo();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put("/productos/{$producto->id}", $this->datosEdicion($producto, [
            'atributo_material' => 'Madera',
        ]))->assertSessionHasErrors('atributo_material');

        // La transacción se deshace: el producto conserva su valor anterior.
        $this->assertSame(['material' => 'Latón'], $producto->fresh()->obtenerAtributos());
    }

    public function test_conserva_un_valor_anterior_que_no_esta_en_la_lista(): void
    {
        $producto = $this->cerraduraDeEjemplo();
        $producto->atributos()->where('clave', 'material')->update(['valor' => 'Bronce antiguo']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put("/productos/{$producto->id}", $this->datosEdicion($producto, [
            'atributo_material' => 'Bronce antiguo',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['material' => 'Bronce antiguo'], $producto->fresh()->obtenerAtributos());
    }

    public function test_nueva_categoria_guarda_las_opciones_escritas_con_comas(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        $this->post('/categorias', [
            'nombre' => 'Tiradores',
            'atributos' => [
                ['nombre' => 'Material', 'opciones' => 'Aluminio, Acero , ,Zamak, aluminio'],
                ['nombre' => 'Largo', 'opciones' => ''],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect('/productos/create');

        $categoria = Categoria::where('slug', 'tiradores')->firstOrFail();
        $atributos = $categoria->atributos()->orderBy('id')->get();

        $this->assertSame(['Aluminio', 'Acero', 'Zamak'], $atributos[0]->opciones);
        $this->assertNull($atributos[1]->opciones);
    }

    public function test_nueva_categoria_no_acepta_atributos_repetidos(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/categorias', [
            'nombre' => 'Rieles',
            'atributos' => [
                ['nombre' => 'Color', 'opciones' => 'Negro, Blanco'],
                ['nombre' => 'color', 'opciones' => ''],
            ],
        ])->assertSessionHasErrors('atributos');

        $this->assertFalse(Categoria::where('slug', 'rieles')->exists());
    }
}
