<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecepcionTest extends TestCase
{
    use RefreshDatabase;

    private function importacion(int $cantidadSku = 1): Importacion
    {
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor de prueba']);
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);
        $importacion = Importacion::create([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-'.$proveedor->id,
            'numero_contenedor' => 'C-'.$proveedor->id,
            'fecha_llegada' => '2026-10-16',
            'estado' => 'En recepción',
        ]);

        for ($i = 1; $i <= $cantidadSku; $i++) {
            $producto = Producto::create([
                'codigo' => 'SKU-'.$proveedor->id.'-'.$i,
                'nombre' => 'Bisagra '.$i,
                'categoria_id' => $categoria->id,
                'proveedor_id' => $proveedor->id,
                'stock' => 25,
                'unidades_por_caja' => 10,
            ]);
            $importacion->detalles()->create([
                'producto_id' => $producto->id,
                'producto_codigo' => $producto->codigo,
                'cajas_facturadas' => 10,
            ]);
        }

        return $importacion->load('detalles');
    }

    private function contar(Importacion $importacion, array $conteos): void
    {
        $this->patch('/importaciones/'.$importacion->id.'/conteo', ['conteos' => $conteos])
            ->assertSessionHasNoErrors();
    }

    private function merma(int $detalleId, int $cantidad)
    {
        return $this->post('/mermas', [
            'importacion_detalle_id' => $detalleId,
            'cantidad' => $cantidad,
            'motivo' => 'Cajas rotas',
            'fecha_registro' => '2026-10-17',
        ]);
    }

    public function test_cerrar_suma_al_stock_las_cajas_contadas_menos_las_mermas(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        // Facturadas 10, llegan 8 y 2 están dañadas: entran 6 cajas de 10 unidades.
        $this->contar($importacion, [$detalle->id => 8]);
        $this->merma($detalle->id, 2)->assertSessionHasNoErrors();

        $this->patch('/importaciones/'.$importacion->id.'/cerrar')
            ->assertSessionHasNoErrors()
            ->assertRedirect('/importaciones/'.$importacion->id);

        $this->assertSame(25 + 60, $detalle->producto->fresh()->stock);
        $this->assertSame('Procesada', $importacion->fresh()->estado);

        $this->get('/importaciones/'.$importacion->id)->assertOk()
            ->assertSee('Recepción cerrada')
            ->assertDontSee('Cerrar recepción y actualizar stock');
    }

    public function test_no_cierra_sin_el_conteo_de_todos_los_sku(): void
    {
        $importacion = $this->importacion(2);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch('/importaciones/'.$importacion->id.'/cerrar')
            ->assertSessionHasErrors('cierre');

        $this->assertSame('En recepción', $importacion->fresh()->estado);
    }

    public function test_no_cierra_con_sku_pendientes_de_registro(): void
    {
        $importacion = $this->importacion();
        $pendiente = $importacion->detalles()->create([
            'producto_codigo' => 'SKU-NUEVO',
            'cajas_facturadas' => 5,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'inventario']));
        $this->contar($importacion, [$importacion->detalles->first()->id => 10, $pendiente->id => 5]);

        $this->patch('/importaciones/'.$importacion->id.'/cerrar')->assertSessionHasErrors('cierre');
        $this->assertSame(25, $importacion->detalles->first()->producto->fresh()->stock);
    }

    public function test_no_se_cierra_dos_veces(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));
        $this->contar($importacion, [$detalle->id => 10]);

        $this->patch('/importaciones/'.$importacion->id.'/cerrar')->assertSessionHasNoErrors();
        $this->patch('/importaciones/'.$importacion->id.'/cerrar')->assertSessionHasErrors('cierre');

        $this->assertSame(125, $detalle->producto->fresh()->stock);
    }

    public function test_ventas_no_puede_cerrar_una_recepcion(): void
    {
        $importacion = $this->importacion();
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->patch('/importaciones/'.$importacion->id.'/cerrar')
            ->assertForbidden();
    }

    public function test_la_merma_exige_conteo_y_no_supera_las_cajas_contadas(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        $this->merma($detalle->id, 1)->assertSessionHasErrors('cantidad');

        $this->contar($importacion, [$detalle->id => 6]);
        // Hay 10 facturadas pero solo llegaron 6.
        $this->merma($detalle->id, 7)->assertSessionHasErrors('cantidad');
        $this->merma($detalle->id, 6)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('mermas', 1);
    }

    public function test_el_conteo_no_puede_quedar_por_debajo_de_las_mermas(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));
        $this->contar($importacion, [$detalle->id => 8]);
        $this->merma($detalle->id, 5)->assertSessionHasNoErrors();

        $this->patch('/importaciones/'.$importacion->id.'/conteo', ['conteos' => [$detalle->id => 4]])
            ->assertSessionHasErrors('conteos.'.$detalle->id);
        $this->assertSame(8, $detalle->conteoFisico->fresh()->cajas_contadas);
    }

    public function test_las_mermas_de_una_importacion_cerrada_no_se_modifican(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->contar($importacion, [$detalle->id => 8]);
        $this->merma($detalle->id, 2)->assertSessionHasNoErrors();
        $merma = Merma::firstOrFail();
        $this->patch('/importaciones/'.$importacion->id.'/cerrar')->assertSessionHasNoErrors();

        $this->merma($detalle->id, 1)->assertSessionHasErrors('cantidad');
        $this->get('/mermas/'.$merma->id.'/editar')->assertRedirect('/mermas');
        $this->put('/mermas/'.$merma->id, [
            'importacion_detalle_id' => $detalle->id,
            'cantidad' => 1,
            'motivo' => 'Cajas rotas',
            'fecha_registro' => '2026-10-17',
        ])->assertSessionHasErrors('cantidad');
        $this->delete('/mermas/'.$merma->id)->assertSessionHas('error');

        $this->assertSame(2, $merma->fresh()->cantidad);
        $this->get('/mermas')->assertOk()->assertSee('Importación cerrada');
    }

    public function test_las_pantallas_de_mermas_soportan_sku_pendientes_de_registro(): void
    {
        $importacion = $this->importacion();
        $pendiente = $importacion->detalles()->create([
            'producto_codigo' => 'SKU-NUEVO',
            'cajas_facturadas' => 5,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->contar($importacion, [$importacion->detalles->first()->id => 10, $pendiente->id => 5]);
        $this->merma($pendiente->id, 1)->assertSessionHasNoErrors();

        $this->get('/mermas/crear')->assertOk()->assertSee('SKU-NUEVO');
        $this->get('/mermas')->assertOk()->assertSee('SKU-NUEVO');
        $this->get('/mermas/'.Merma::firstOrFail()->id.'/editar')->assertOk()->assertSee('SKU-NUEVO');
    }

    public function test_el_producto_nuevo_nace_sin_stock(): void
    {
        $importacion = $this->importacion();
        $pendiente = $importacion->detalles()->create([
            'producto_codigo' => 'SKU-NUEVO',
            'cajas_facturadas' => 5,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->post('/productos', [
                'importacion_detalle_id' => $pendiente->id,
                'nombre' => 'Bisagra nueva',
                'categoria_id' => Categoria::firstOrFail()->id,
                'stock' => 500,
                'unidades_por_caja' => 12,
            ])->assertSessionHasNoErrors();

        $this->assertSame(0, Producto::where('codigo', 'SKU-NUEVO')->firstOrFail()->stock);
    }
}
