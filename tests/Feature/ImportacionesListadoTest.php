<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportacionesListadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Proveedor $ningbo;
    private Proveedor $global;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ningbo = Proveedor::create(['razon_social' => 'Ningbo Hardware']);
        $this->global = Proveedor::create(['razon_social' => 'Global Trade']);
    }

    // Crea una importación con sus SKU: [código, cajas, contadas|null, registrado como producto].
    private function importacion(Proveedor $proveedor, string $factura, string $estado, array $skus): Importacion
    {
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);

        $importacion = Importacion::create([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => $factura,
            'fecha_llegada' => '2026-11-20',
            'estado' => $estado,
        ]);

        foreach ($skus as [$codigo, $cajas, $contadas, $registrado]) {
            $producto = $registrado ? Producto::firstOrCreate(['codigo' => $codigo], [
                'nombre' => 'Producto '.$codigo,
                'categoria_id' => $categoria->id,
                'proveedor_id' => $proveedor->id,
                'stock' => 0,
                'unidades_por_caja' => 10,
            ]) : null;

            $detalle = $importacion->detalles()->create([
                'producto_id' => $producto?->id,
                'producto_codigo' => $codigo,
                'cajas_facturadas' => $cajas,
            ]);

            if ($contadas !== null) {
                $detalle->conteoFisico()->create([
                    'cajas_contadas' => $contadas,
                    'diferencia' => $contadas - $cajas,
                    'registrado_por' => $this->admin->id,
                ]);
            }
        }

        return $importacion;
    }

    public function test_muestra_una_fila_por_importacion_con_su_resumen(): void
    {
        $this->importacion($this->ningbo, 'NB-1', 'En recepción', [
            ['SKU-A', 8, null, true],
            ['SKU-B', 6, null, true],
            ['SKU-C', 4, null, true],
        ]);

        $respuesta = $this->actingAs($this->admin)->get('/importaciones')->assertOk();

        // Una fila principal (con botón para desplegar) y los SKU dentro del detalle.
        $this->assertSame(1, substr_count($respuesta->getContent(), 'class="btn-desplegar"'));
        $respuesta->assertSee('3 SKU')
            ->assertSee('18 cajas')
            ->assertSee('SKU-A')
            ->assertSee('SKU-C');
    }

    public function test_el_avance_indica_lo_que_falta_para_cerrar(): void
    {
        $this->importacion($this->ningbo, 'NB-SIN-CONTEO', 'En recepción', [['SKU-A', 5, null, true]]);
        $this->importacion($this->ningbo, 'NB-SIN-REGISTRO', 'En recepción', [['SKU-B', 5, 5, false]]);
        $this->importacion($this->global, 'GT-LISTA', 'En recepción', [['SKU-C', 5, 5, true]]);
        $this->importacion($this->global, 'GT-CERRADA', 'Procesada', [['SKU-D', 5, 5, true]]);

        $this->actingAs($this->admin)->get('/importaciones')
            ->assertOk()
            ->assertSee('Falta el conteo físico')
            ->assertSee('1 SKU sin registrar')
            ->assertSee('Lista para cerrar')
            ->assertSee('Stock ingresado');
    }

    public function test_filtra_por_estado_proveedor_y_busqueda(): void
    {
        $this->importacion($this->ningbo, 'NB-ABIERTA', 'En recepción', [['BIS-001', 5, null, true]]);
        $this->importacion($this->global, 'GT-CERRADA', 'Procesada', [['TIR-002', 5, 5, true]]);

        $this->actingAs($this->admin)->get('/importaciones?estado=Procesada')
            ->assertSee('GT-CERRADA')->assertDontSee('NB-ABIERTA');

        $this->actingAs($this->admin)->get('/importaciones?proveedor='.$this->ningbo->id)
            ->assertSee('NB-ABIERTA')->assertDontSee('GT-CERRADA');

        // La búsqueda encuentra la importación por uno de sus SKU.
        $this->actingAs($this->admin)->get('/importaciones?buscar=TIR-002')
            ->assertSee('GT-CERRADA')->assertDontSee('NB-ABIERTA');

        // Un estado inválido se ignora y muestra todas.
        $this->actingAs($this->admin)->get('/importaciones?estado=otro')
            ->assertSee('GT-CERRADA')->assertSee('NB-ABIERTA');

        $this->actingAs($this->admin)->get('/importaciones?buscar=NO-EXISTE')
            ->assertSee('No hay importaciones que coincidan con los filtros.');
    }
}
