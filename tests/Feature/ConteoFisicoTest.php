<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ConteoFisico;
use App\Models\Importacion;
use App\Models\ImportacionDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ConteoFisicoTest extends TestCase
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
                'cajas_facturadas' => 10,
            ]);
        }

        return $importacion->load('detalles');
    }

    private function url(Importacion $importacion): string
    {
        return '/importaciones/'.$importacion->id.'/conteo';
    }

    public function test_inventario_puede_seleccionar_importaciones_y_ver_todos_los_sku(): void
    {
        $importacion = $this->importacion(3);
        $usuario = User::factory()->create(['role' => 'inventario']);

        $this->actingAs($usuario)->get('/importaciones')->assertOk()
            ->assertSee('Ver conteo')->assertDontSee('Nueva Importación');
        $this->get('/importaciones/'.$importacion->id)->assertOk()
            ->assertSee('SKU-1-1')->assertSee('SKU-1-2')->assertSee('SKU-1-3')
            ->assertSee('Cajas Facturadas')->assertSee('Cajas Contadas')->assertSee('Diferencia')
            ->assertSee('Sin contar')->assertSee('Guardar conteo físico');
        $this->get('/importaciones/create')->assertForbidden();
        $this->get('/proveedores')->assertForbidden();
    }

    public function test_guarda_diferencias_por_sku_y_recalcula_sin_confiar_en_el_cliente(): void
    {
        $importacion = $this->importacion(3);
        $usuario = User::factory()->create(['role' => 'inventario']);
        $detalles = $importacion->detalles;

        $this->actingAs($usuario)->patch($this->url($importacion), [
            'conteos' => [$detalles[0]->id => 8, $detalles[1]->id => 13, $detalles[2]->id => 10],
            'diferencia' => 999,
            'cajas_facturadas' => 999,
        ])->assertSessionHasNoErrors()->assertRedirect('/importaciones/'.$importacion->id);

        foreach ([-2, 3, 0] as $index => $diferencia) {
            $this->assertDatabaseHas('conteos_fisicos', [
                'importacion_detalle_id' => $detalles[$index]->id,
                'diferencia' => $diferencia,
                'registrado_por' => $usuario->id,
            ]);
            $this->assertSame(25, $detalles[$index]->producto->fresh()->stock);
            $this->assertSame(10, $detalles[$index]->fresh()->cajas_facturadas);
        }
        $this->get('/importaciones/'.$importacion->id)->assertOk()
            ->assertSee('-2 (Faltante)')->assertSee('+3 (Sobrante)')->assertSee('0 (Coincide)')
            ->assertSee('text-bg-danger')->assertSee('text-bg-warning')->assertSee('text-bg-success');
    }

    public function test_cero_cajas_es_un_conteo_valido_y_persistente(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->patch($this->url($importacion), ['conteos' => [$detalle->id => 0]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('conteos_fisicos', [
            'importacion_detalle_id' => $detalle->id, 'cajas_contadas' => 0, 'diferencia' => -10,
        ]);
        $this->get('/importaciones/'.$importacion->id)->assertSee('-10 (Faltante)')->assertSee('value="0"', false);
    }

    public function test_admin_puede_corregir_el_conteo_sin_duplicar_el_registro(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([8, 12] as $contadas) {
            $this->patch($this->url($importacion), ['conteos' => [$detalle->id => $contadas]])
                ->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('conteos_fisicos', 1);
        $this->assertSame(12, $detalle->conteoFisico->cajas_contadas);
        $this->assertSame(2, $detalle->conteoFisico->diferencia);
    }

    #[TestWith([-1])]
    #[TestWith([1.5])]
    #[TestWith(['abc'])]
    #[TestWith([''])]
    #[TestWith([null])]
    #[TestWith([4294967296])]
    public function test_rechaza_cantidades_invalidas_sin_guardar_nada(mixed $valor): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->from('/importaciones/'.$importacion->id)
            ->patch($this->url($importacion), ['conteos' => [$detalle->id => $valor]])
            ->assertSessionHasErrors('conteos.'.$detalle->id);
        $this->assertDatabaseCount('conteos_fisicos', 0);
        // El formulario debe seguir visible tras la validación fallida.
        $this->get('/importaciones/'.$importacion->id)->assertOk();
    }

    public function test_rechaza_detalles_de_otra_importacion_sin_guardado_parcial(): void
    {
        $importacion = $this->importacion();
        $otra = $this->importacion();
        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->patch($this->url($importacion), ['conteos' => [
                $importacion->detalles->first()->id => 10,
                $otra->detalles->first()->id => 15,
            ]])->assertSessionHasErrors('conteos');
        $this->assertDatabaseCount('conteos_fisicos', 0);
    }

    public function test_requiere_el_conteo_de_cada_sku_de_la_importacion(): void
    {
        $importacion = $this->importacion(2);
        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->patch($this->url($importacion), ['conteos' => [$importacion->detalles->first()->id => 10]])
            ->assertSessionHasErrors('conteos');
        $this->assertDatabaseCount('conteos_fisicos', 0);
    }

    public function test_conteos_de_un_mismo_producto_en_importaciones_distintas_son_independientes(): void
    {
        $primera = $this->importacion();
        $segunda = $this->importacion();
        $detalle = $segunda->detalles->first();
        $detalle->update(['producto_id' => $primera->detalles->first()->producto_id]);
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        foreach ([[$primera, 8], [$segunda, 12]] as [$importacion, $cantidad]) {
            $this->patch($this->url($importacion), ['conteos' => [$importacion->detalles->first()->id => $cantidad]])
                ->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('conteos_fisicos', 2);
        $this->assertSame(-2, $primera->detalles->first()->conteoFisico->diferencia);
        $this->assertSame(2, $segunda->detalles->first()->conteoFisico->diferencia);
    }

    public function test_ventas_y_visitantes_no_pueden_registrar_conteos(): void
    {
        $importacion = $this->importacion();
        $datos = ['conteos' => [$importacion->detalles->first()->id => 10]];
        $this->patch($this->url($importacion), $datos)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get('/importaciones')->assertForbidden();
        $this->get('/importaciones/'.$importacion->id)->assertForbidden();
        $this->patch($this->url($importacion), $datos)->assertForbidden();
        $this->assertDatabaseCount('conteos_fisicos', 0);
    }

    public function test_no_permite_alterar_el_conteo_de_una_importacion_cerrada(): void
    {
        $importacion = $this->importacion();
        $detalle = $importacion->detalles->first();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));
        $this->patch($this->url($importacion), ['conteos' => [$detalle->id => 10]])->assertSessionHasNoErrors();
        $importacion->update(['estado' => 'Procesada']);

        $this->patch($this->url($importacion), ['conteos' => [$detalle->id => 5]])->assertSessionHasErrors('conteos');
        $this->assertSame(10, $detalle->conteoFisico->cajas_contadas);
        $this->get('/importaciones/'.$importacion->id)->assertOk()->assertDontSee('Guardar conteo físico');
    }

    public function test_las_importaciones_se_cargan_sin_duplicados_ni_reemplazar_conteos(): void
    {
        $this->seed(\Database\Seeders\ImportacionSeeder::class);
        $this->assertDatabaseCount('productos', 8);
        $this->assertDatabaseCount('importaciones', 3);
        $this->assertDatabaseCount('importacion_detalles', 8);
        $this->assertDatabaseCount('conteos_fisicos', 6);

        $conteo = ConteoFisico::firstOrFail();
        $conteo->update(['cajas_contadas' => 77, 'diferencia' => 77 - $conteo->detalle->cajas_facturadas]);
        $this->seed(\Database\Seeders\ImportacionSeeder::class);

        $this->assertDatabaseCount('productos', 8);
        $this->assertDatabaseCount('importaciones', 3);
        $this->assertDatabaseCount('importacion_detalles', 8);
        $this->assertDatabaseCount('conteos_fisicos', 6);
        $this->assertSame(77, $conteo->fresh()->cajas_contadas);
    }

}
