<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdjuntosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function importacionContada(): Importacion
    {
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor de prueba']);
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);
        $producto = Producto::create([
            'codigo' => 'SKU-ADJ',
            'nombre' => 'Bisagra',
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'stock' => 0,
            'unidades_por_caja' => 10,
        ]);

        $importacion = Importacion::create([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-1',
            'fecha_llegada' => '2026-11-20',
            'estado' => 'En recepción',
        ]);

        $detalle = $importacion->detalles()->create([
            'producto_id' => $producto->id,
            'producto_codigo' => $producto->codigo,
            'cajas_facturadas' => 10,
        ]);

        $detalle->conteoFisico()->create([
            'cajas_contadas' => 10,
            'diferencia' => 0,
            'registrado_por' => User::factory()->create(['role' => 'inventario'])->id,
        ]);

        return $importacion->load('detalles.conteoFisico');
    }

    private function registrarMerma(Importacion $importacion, ?UploadedFile $foto)
    {
        return $this->post('/mermas', array_filter([
            'importacion_detalle_id' => $importacion->detalles[0]->id,
            'cantidad' => 2,
            'motivo' => 'Caja golpeada',
            'fecha_registro' => '2026-11-20',
            'imagen' => $foto,
        ]));
    }

    public function test_la_foto_de_merma_se_guarda_en_privado_y_se_ve_desde_el_historial(): void
    {
        $importacion = $this->importacionContada();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        $this->registrarMerma($importacion, UploadedFile::fake()->image('golpe.jpg'))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/mermas');

        $merma = Merma::firstOrFail();
        Storage::disk('local')->assertExists($merma->imagen);
        Storage::disk('public')->assertMissing($merma->imagen);

        $this->get('/mermas')->assertOk()->assertSee(route('mermas.imagen', $merma), false);
        $this->get(route('mermas.imagen', $merma))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_la_foto_de_merma_no_es_publica_ni_visible_para_ventas(): void
    {
        $importacion = $this->importacionContada();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));
        $this->registrarMerma($importacion, UploadedFile::fake()->image('golpe.png'));
        $merma = Merma::firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get(route('mermas.imagen', $merma))->assertForbidden();

        auth()->logout();
        $this->get(route('mermas.imagen', $merma))->assertRedirect('/login');
    }

    public function test_reemplazar_y_eliminar_la_merma_borra_las_fotos(): void
    {
        $importacion = $this->importacionContada();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->registrarMerma($importacion, UploadedFile::fake()->image('primera.jpg'));
        $merma = Merma::firstOrFail();
        $primera = $merma->imagen;

        $this->put(route('mermas.update', $merma), [
            'importacion_detalle_id' => $merma->importacion_detalle_id,
            'cantidad' => 3,
            'motivo' => 'Caja golpeada',
            'fecha_registro' => '2026-11-20',
            'imagen' => UploadedFile::fake()->image('segunda.jpg'),
        ])->assertSessionHasNoErrors();

        $segunda = $merma->fresh()->imagen;
        Storage::disk('local')->assertMissing($primera);
        Storage::disk('local')->assertExists($segunda);

        $this->delete(route('mermas.destroy', $merma))->assertSessionHas('success');
        Storage::disk('local')->assertMissing($segunda);
    }

    public function test_rechaza_archivos_que_no_son_imagen_como_evidencia(): void
    {
        $importacion = $this->importacionContada();
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        $this->registrarMerma($importacion, UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'))
            ->assertSessionHasErrors('imagen');

        $this->assertSame(0, Merma::count());
    }

    public function test_las_fotos_subidas_antes_al_disco_publico_se_siguen_viendo(): void
    {
        $importacion = $this->importacionContada();
        Storage::disk('public')->put('mermas/antigua.jpg', 'contenido');
        $merma = Merma::create([
            'importacion_detalle_id' => $importacion->detalles[0]->id,
            'cantidad' => 1,
            'motivo' => 'Caja golpeada',
            'fecha_registro' => '2026-11-20',
            'imagen' => 'mermas/antigua.jpg',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('mermas.imagen', $merma))->assertOk();
    }

    public function test_la_foto_del_conteo_fisico_se_sirve_por_ruta_protegida(): void
    {
        $importacion = $this->importacionContada();
        $detalle = $importacion->detalles[0];
        $this->actingAs(User::factory()->create(['role' => 'inventario']));

        $this->patch('/importaciones/'.$importacion->id.'/conteo', [
            'conteos' => [$detalle->id => 10],
            'imagenes' => [$detalle->id => UploadedFile::fake()->image('conteo.jpg')],
        ])->assertSessionHasNoErrors();

        $conteo = $detalle->conteoFisico()->firstOrFail();
        Storage::disk('local')->assertExists($conteo->imagen);

        $this->get('/importaciones/'.$importacion->id)->assertOk()
            ->assertSee(route('conteos.imagen', $conteo), false);
        $this->get(route('conteos.imagen', $conteo))->assertOk();
    }

    public function test_la_factura_pdf_se_previsualiza_y_descarga(): void
    {
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor de prueba']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/importaciones', [
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-200',
            'fecha_llegada' => '2026-11-20',
            'archivo_factura' => UploadedFile::fake()->create('factura.pdf', 50, 'application/pdf'),
            'detalles' => [['producto_codigo' => 'SKU-NUEVO', 'cajas_facturadas' => 5]],
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::firstOrFail();
        Storage::disk('local')->assertExists($importacion->archivo_factura);

        $this->get('/importaciones/'.$importacion->id)->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee(route('importaciones.factura', $importacion), false);

        $this->get(route('importaciones.factura', $importacion))->assertOk();
        $this->get(route('importaciones.factura.descargar', $importacion))
            ->assertOk()
            ->assertDownload('factura-importacion-'.$importacion->id.'.pdf');
    }
}
