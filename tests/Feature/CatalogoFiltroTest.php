<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoFiltroTest extends TestCase
{
    use RefreshDatabase;

    private Categoria $bisagras;
    private Categoria $correderas;
    private Categoria $pistones;

    protected function setUp(): void
    {
        parent::setUp();

        $proveedor = Proveedor::create(['razon_social' => 'Proveedor de prueba']);

        $this->bisagras = Categoria::create(['nombre' => 'Bisagras', 'slug' => 'bisagras-filtro']);
        $this->correderas = Categoria::create(['nombre' => 'Correderas', 'slug' => 'correderas-filtro']);
        $this->pistones = Categoria::create(['nombre' => 'Pistones', 'slug' => 'pistones-filtro']);

        foreach ([
            ['BIS-01', 'Bisagra cierre lento', $this->bisagras],
            ['BIS-02', 'Bisagra recta', $this->bisagras],
            ['COR-01', 'Corredera telescópica', $this->correderas],
        ] as [$codigo, $nombre, $categoria]) {
            Producto::create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'categoria_id' => $categoria->id,
                'proveedor_id' => $proveedor->id,
                'stock' => 50,
                'unidades_por_caja' => 10,
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => 'ventas']));
    }

    public function test_sin_filtro_muestra_todo_y_los_contadores_por_categoria(): void
    {
        $respuesta = $this->get('/productos')->assertOk()
            ->assertSee('BIS-01')->assertSee('BIS-02')->assertSee('COR-01');

        $categorias = $respuesta->viewData('conteoPorCategoria');
        $this->assertSame(2, (int) $categorias[$this->bisagras->id]);
        $this->assertSame(1, (int) $categorias[$this->correderas->id]);
        $this->assertArrayNotHasKey($this->pistones->id, $categorias->all());
        $this->assertNull($respuesta->viewData('categoriaActual'));
    }

    public function test_filtra_por_categoria(): void
    {
        $this->get('/productos?categoria='.$this->bisagras->id)->assertOk()
            ->assertSee('BIS-01')->assertSee('BIS-02')
            ->assertDontSee('COR-01')
            ->assertSee('2 productos');
    }

    public function test_combina_la_categoria_con_la_busqueda(): void
    {
        $respuesta = $this->get('/productos?categoria='.$this->bisagras->id.'&buscar=recta')->assertOk()
            ->assertSee('BIS-02')
            ->assertDontSee('BIS-01')
            ->assertDontSee('COR-01')
            // El buscador conserva la categoría y los filtros conservan la búsqueda.
            ->assertSee('name="categoria" value="'.$this->bisagras->id.'"', false)
            ->assertSee('buscar=recta', false);

        // Los contadores reflejan solo lo que coincide con la búsqueda.
        $conteo = $respuesta->viewData('conteoPorCategoria');
        $this->assertSame(1, (int) $conteo[$this->bisagras->id]);
        $this->assertArrayNotHasKey($this->correderas->id, $conteo->all());
    }

    public function test_una_categoria_sin_productos_muestra_mensaje(): void
    {
        $this->get('/productos?categoria='.$this->pistones->id)->assertOk()
            ->assertSee('No se encontraron productos con estos filtros.')
            ->assertDontSee('BIS-01');
    }

    public function test_un_valor_de_categoria_invalido_muestra_todo(): void
    {
        foreach (['999999', 'abc', ''] as $valor) {
            $this->get('/productos?categoria='.$valor)->assertOk()
                ->assertSee('BIS-01')->assertSee('COR-01');
        }
    }

    public function test_la_paginacion_conserva_el_filtro(): void
    {
        $proveedor = Proveedor::firstOrFail();

        for ($i = 3; $i <= 20; $i++) {
            Producto::create([
                'codigo' => sprintf('BIS-%02d', $i),
                'nombre' => 'Bisagra '.$i,
                'categoria_id' => $this->bisagras->id,
                'proveedor_id' => $proveedor->id,
                'stock' => 10,
                'unidades_por_caja' => 10,
            ]);
        }

        $this->get('/productos?categoria='.$this->bisagras->id)->assertOk()
            ->assertSee('categoria='.$this->bisagras->id.'&amp;page=2', false);
    }
}
