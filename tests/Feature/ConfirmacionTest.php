<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Importacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmacionTest extends TestCase
{
    use RefreshDatabase;

    // Nombres con comillas, tildes y etiquetas para comprobar el escapado del JSON.
    private const NOMBRE_RARO = 'O\'Brien "Ñandú" <b>&</b>';

    private Producto $producto;
    private Importacion $importacion;
    private Venta $venta;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $proveedor = Proveedor::create(['razon_social' => self::NOMBRE_RARO, 'pais_origen' => 'Perú', 'activo' => true]);
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);

        $this->producto = Producto::create([
            'codigo' => 'SKU-CONF',
            'nombre' => self::NOMBRE_RARO,
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'stock' => 100,
            'unidades_por_caja' => 10,
            'precio_venta' => 5,
        ]);

        $this->cliente = Cliente::create(['nombre' => self::NOMBRE_RARO, 'tipo_documento' => 'DNI', 'numero_documento' => '12345678']);

        $this->importacion = Importacion::create([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-"1"',
            'fecha_llegada' => '2026-11-20',
            'estado' => 'En recepción',
        ]);

        $detalle = $this->importacion->detalles()->create([
            'producto_id' => $this->producto->id,
            'producto_codigo' => $this->producto->codigo,
            'cajas_facturadas' => 5,
        ]);

        $usuario = User::factory()->create(['role' => 'admin']);

        $detalle->conteoFisico()->create(['cajas_contadas' => 5, 'diferencia' => 0, 'registrado_por' => $usuario->id]);

        $this->venta = Venta::create([
            'cliente_id' => $this->cliente->id,
            'fecha_venta' => '2026-11-20',
            'subtotal' => 10,
            'igv' => 1.8,
            'total' => 11.8,
            'estado' => Venta::VIGENTE,
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario);
    }

    // Devuelve los formularios con confirmación de una página: [accion => atributos].
    private function formulariosConConfirmacion(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $formularios = [];

        foreach ((new DOMXPath($dom))->query('//form[@data-confirm]') as $form) {
            $datos = $form->getAttribute('data-confirm-datos');

            $formularios[] = [
                'accion' => $form->getAttribute('action'),
                'mensaje' => $form->getAttribute('data-confirm'),
                'titulo' => $form->getAttribute('data-confirm-titulo'),
                'boton' => $form->getAttribute('data-confirm-boton'),
                'datos' => $datos === '' ? null : json_decode($datos, true, flags: JSON_THROW_ON_ERROR),
            ];
        }

        return $formularios;
    }

    private function buscar(array $formularios, string $accion): array
    {
        foreach ($formularios as $formulario) {
            if (str_ends_with($formulario['accion'], $accion)) {
                return $formulario;
            }
        }

        $this->fail("No hay un formulario con confirmación que envíe a {$accion}.");
    }

    public function test_los_formularios_de_registro_y_edicion_piden_confirmacion(): void
    {
        $paginas = [
            '/ventas/crear' => '/ventas',
            "/ventas/{$this->venta->id}/editar" => "/ventas/{$this->venta->id}",
            '/clientes/create' => '/clientes',
            "/clientes/{$this->cliente->id}/edit" => "/clientes/{$this->cliente->id}",
            '/proveedores/create' => '/proveedores',
            "/proveedores/{$this->producto->proveedor_id}/edit" => "/proveedores/{$this->producto->proveedor_id}",
            '/productos/create' => '/productos',
            "/productos/{$this->producto->id}/edit" => "/productos/{$this->producto->id}",
            '/categorias/nueva' => '/categorias',
            '/importaciones/create' => '/importaciones',
            '/mermas/crear' => '/mermas',
        ];

        foreach ($paginas as $pagina => $accion) {
            $formulario = $this->buscar($this->formulariosConConfirmacion($pagina), $accion);

            $this->assertNotSame('', $formulario['titulo'], "Falta el título en {$pagina}");
            $this->assertNotSame('', $formulario['boton'], "Falta el texto del botón en {$pagina}");
        }
    }

    public function test_el_conteo_y_el_cierre_de_recepcion_muestran_sus_datos(): void
    {
        $formularios = $this->formulariosConConfirmacion("/importaciones/{$this->importacion->id}");

        $conteo = $this->buscar($formularios, "/importaciones/{$this->importacion->id}/conteo");
        $this->assertSame('F-"1"', $conteo['datos']['Factura']);

        $cierre = $this->buscar($formularios, "/importaciones/{$this->importacion->id}/cerrar");
        $this->assertSame('Cerrar recepción', $cierre['boton']);
        $this->assertSame(5, $cierre['datos']['Cajas en buen estado']);
        $this->assertSame(50, $cierre['datos']['Unidades que entrarán al stock']);
    }

    public function test_eliminar_y_anular_muestran_que_registro_se_afecta(): void
    {
        $producto = $this->buscar($this->formulariosConConfirmacion('/productos'), "/productos/{$this->producto->id}");
        $this->assertSame(self::NOMBRE_RARO, $producto['datos']['Producto']);

        $ficha = $this->buscar($this->formulariosConConfirmacion("/productos/{$this->producto->id}"), "/productos/{$this->producto->id}/ajuste");
        $this->assertSame('100 unidades', $ficha['datos']['Stock actual']);

        $cliente = $this->buscar($this->formulariosConConfirmacion('/clientes'), "/clientes/{$this->cliente->id}");
        $this->assertSame(self::NOMBRE_RARO, $cliente['datos']['Cliente']);
        $this->assertSame('DNI 12345678', $cliente['datos']['Documento']);

        $proveedor = $this->buscar($this->formulariosConConfirmacion('/proveedores'), "/proveedores/{$this->producto->proveedor_id}");
        $this->assertSame(self::NOMBRE_RARO, $proveedor['datos']['Proveedor']);

        $anular = $this->buscar($this->formulariosConConfirmacion("/ventas/{$this->venta->id}"), "/ventas/{$this->venta->id}/anular");
        $this->assertSame($this->venta->numero, $anular['datos']['Venta']);
        $this->assertSame('S/ 11.80', $anular['datos']['Total']);
    }

    public function test_el_modal_usa_el_boton_rojo_y_carga_el_script(): void
    {
        $this->get('/dashboard')->assertOk()
            ->assertSee('id="btnAceptarConfirmacion"', false)
            ->assertSee('class="btn btn-rojo" id="btnAceptarConfirmacion"', false)
            ->assertSee('id="resumenConfirmacion"', false)
            ->assertSee('js/confirmacion.js', false);
    }
}
