<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KardexVentasTest extends TestCase
{
    use RefreshDatabase;

    private function producto(string $codigo, int $stock, float $precio = 10.00): Producto
    {
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);
        $proveedor = Proveedor::firstOrCreate(['razon_social' => 'Proveedor de prueba']);

        return Producto::create([
            'codigo' => $codigo,
            'nombre' => 'Producto '.$codigo,
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'stock' => $stock,
            'unidades_por_caja' => 10,
            'precio_venta' => $precio,
        ]);
    }

    private function venta(array $lineas, ?int $clienteId = null): Venta
    {
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->post('/ventas', [
                'cliente_id' => $clienteId,
                'fecha_venta' => '2026-10-26',
                'productos' => $lineas,
            ])
            ->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    public function test_cada_venta_queda_registrada_en_el_kardex(): void
    {
        $producto = $this->producto('KAR-1', 10);

        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 4]]);

        $movimiento = MovimientoInventario::where('tipo', 'venta')->firstOrFail();
        $this->assertSame(-4, $movimiento->cantidad);
        $this->assertSame(6, $movimiento->saldo);
        $this->assertSame('Venta', $movimiento->referencia_tipo);
        $this->assertSame($venta->id, $movimiento->referencia_id);
        $this->assertSame(6, $producto->fresh()->stock);
    }

    public function test_anular_devuelve_el_stock_y_no_borra_la_venta(): void
    {
        $producto = $this->producto('KAR-2', 10);
        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 3]]);

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->patch("/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Cliente desistió'])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/ventas/{$venta->id}");

        $venta->refresh();
        $this->assertTrue($venta->estaAnulada());
        $this->assertSame('Cliente desistió', $venta->motivo_anulacion);
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(1, MovimientoInventario::where('tipo', 'anulacion_venta')->count());
        $this->assertSame(0, Venta::vigente()->count());
    }

    public function test_una_venta_no_se_anula_dos_veces(): void
    {
        $producto = $this->producto('KAR-3', 10);
        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 1]]);
        $usuario = User::factory()->create(['role' => 'ventas']);

        $this->actingAs($usuario)->patch("/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Error'])
            ->assertSessionHasNoErrors();
        $this->actingAs($usuario)->patch("/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Otra vez'])
            ->assertSessionHasErrors('motivo_anulacion');

        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_editar_venta_reemplaza_lineas_y_ajusta_el_stock(): void
    {
        $bisagra = $this->producto('KAR-4', 10, 4.00);
        $corredera = $this->producto('KAR-5', 10, 12.00);
        $venta = $this->venta([['producto_id' => $bisagra->id, 'cantidad' => 2]]);

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->put("/ventas/{$venta->id}", [
                'cliente_id' => null,
                'fecha_venta' => '2026-10-26',
                'productos' => [
                    ['producto_id' => $bisagra->id, 'cantidad' => 5],
                    ['producto_id' => $corredera->id, 'cantidad' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $bisagra->fresh()->stock);
        $this->assertSame(9, $corredera->fresh()->stock);
        $this->assertSame(2, VentaDetalle::where('venta_id', $venta->id)->count());
        $this->assertSame('32.00', number_format((float) $venta->fresh()->total, 2, '.', ''));
        // Una devolución por la línea anterior, y las salidas: la venta original más las dos líneas nuevas.
        $this->assertSame(1, MovimientoInventario::where('tipo', 'edicion_venta')->count());
        $this->assertSame(3, MovimientoInventario::where('tipo', 'venta')->count());
    }

    public function test_una_venta_anulada_no_se_puede_editar(): void
    {
        $producto = $this->producto('KAR-6', 10);
        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 2]]);
        $usuario = User::factory()->create(['role' => 'ventas']);

        $this->actingAs($usuario)->patch("/ventas/{$venta->id}/anular", ['motivo_anulacion' => 'Error'])
            ->assertSessionHasNoErrors();

        $this->actingAs($usuario)->get("/ventas/{$venta->id}/editar")
            ->assertRedirect("/ventas/{$venta->id}");

        $this->actingAs($usuario)->put("/ventas/{$venta->id}", [
            'fecha_venta' => '2026-10-26',
            'productos' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('productos');

        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_el_stock_no_se_puede_cambiar_desde_la_ficha_del_producto(): void
    {
        $producto = $this->producto('KAR-7', 10);

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->put("/productos/{$producto->id}", [
                'codigo' => 'KAR-7',
                'nombre' => 'Producto editado',
                'categoria_id' => $producto->categoria_id,
                'proveedor_id' => $producto->proveedor_id,
                'unidades_por_caja' => 10,
                'precio_venta' => 10,
                'stock' => 999,
            ])
            ->assertRedirect();

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(0, MovimientoInventario::count());
    }

    public function test_el_ajuste_manual_queda_en_el_kardex_con_su_motivo(): void
    {
        $producto = $this->producto('KAR-8', 10);

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->post("/productos/{$producto->id}/ajuste", [
                'cantidad' => -3,
                'motivo' => 'Conteo físico: faltaban 3 unidades',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(7, $producto->fresh()->stock);

        $movimiento = MovimientoInventario::where('tipo', 'ajuste')->firstOrFail();
        $this->assertSame(-3, $movimiento->cantidad);
        $this->assertSame(7, $movimiento->saldo);
        $this->assertSame('Conteo físico: faltaban 3 unidades', $movimiento->motivo);
    }

    public function test_el_ajuste_exige_motivo_y_no_puede_ser_cero_ni_dejar_stock_negativo(): void
    {
        $producto = $this->producto('KAR-9', 10);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post("/productos/{$producto->id}/ajuste", ['cantidad' => 5])
            ->assertSessionHasErrors('motivo');

        $this->actingAs($admin)->post("/productos/{$producto->id}/ajuste", ['cantidad' => 0, 'motivo' => 'Nada'])
            ->assertSessionHasErrors('cantidad');

        $this->actingAs($admin)->post("/productos/{$producto->id}/ajuste", ['cantidad' => -50, 'motivo' => 'Error'])
            ->assertSessionHasErrors('stock');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(0, MovimientoInventario::count());
    }

    public function test_el_comprobante_se_descarga_en_pdf(): void
    {
        $producto = $this->producto('KAR-10', 10);
        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 2]]);

        $respuesta = $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get("/ventas/{$venta->id}/comprobante");

        $respuesta->assertOk();
        $this->assertStringStartsWith('application/pdf', $respuesta->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_la_venta_guarda_el_cliente_seleccionado(): void
    {
        $producto = $this->producto('KAR-11', 10);
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'tipo_documento' => 'DNI', 'numero_documento' => '70000001']);

        $venta = $this->venta([['producto_id' => $producto->id, 'cantidad' => 1]], $cliente->id);

        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertSame('Cliente de prueba', $venta->nombreCliente());
    }

    public function test_un_cliente_con_ventas_no_se_elimina(): void
    {
        $producto = $this->producto('KAR-12', 10);
        $cliente = Cliente::create(['nombre' => 'Cliente con historial']);
        $this->venta([['producto_id' => $producto->id, 'cantidad' => 1]], $cliente->id);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/clientes/{$cliente->id}")
            ->assertSessionHas('error');

        $this->assertNotNull(Cliente::find($cliente->id));
    }

    public function test_ventas_registra_clientes_y_inventario_no_accede_a_ventas(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->post('/clientes', [
                'nombre' => 'Nuevo cliente',
                'tipo_documento' => 'RUC',
                'numero_documento' => '20333333333',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['nombre' => 'Nuevo cliente', 'numero_documento' => '20333333333']);

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->post('/clientes', ['nombre' => 'Repetido', 'numero_documento' => '20333333333'])
            ->assertSessionHasErrors('numero_documento');

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->get('/ventas')->assertForbidden();
    }

    public function test_el_kardex_se_ve_en_la_ficha_solo_para_admin_e_inventario(): void
    {
        $producto = $this->producto('KAR-13', 10);
        $this->venta([['producto_id' => $producto->id, 'cantidad' => 2]]);

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->get("/productos/{$producto->id}")
            ->assertOk()
            ->assertSee('Historial de movimientos')
            ->assertSee('Venta');

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get("/productos/{$producto->id}")
            ->assertOk()
            ->assertDontSee('Historial de movimientos');
    }

    public function test_cada_rol_ve_su_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/dashboard')->assertOk()->assertSee('Ventas de hoy')->assertSee('Productos con stock bajo');

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->get('/dashboard')->assertOk()->assertSee('Movimientos de hoy');

        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get('/dashboard')->assertOk()->assertSee('Clientes registrados');
    }
}
