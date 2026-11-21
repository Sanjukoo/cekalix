<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    private function producto(string $codigo, int $stock, float $precio): Producto
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

    private function registrar(array $datos)
    {
        return $this->post('/ventas', array_merge([
            'fecha_venta' => '2026-10-24',
        ], $datos));
    }

    public function test_registrar_venta_calcula_total_y_descuenta_stock(): void
    {
        $bisagra = $this->producto('BIS-1', 100, 4.20);
        $corredera = $this->producto('COR-1', 20, 12.50);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $cliente = Cliente::create(['nombre' => 'Mueblería Prueba']);

        $this->registrar([
            'cliente_id' => $cliente->id,
            'productos' => [
                ['producto_id' => $bisagra->id, 'modalidad' => 'unidad', 'cantidad' => 50],
                ['producto_id' => $corredera->id, 'modalidad' => 'unidad', 'cantidad' => 4],
            ],
        ])->assertSessionHasNoErrors();

        $venta = Venta::firstOrFail();

        $this->assertSame($cliente->id, $venta->cliente_id);
        // 50 × 4.20 + 4 × 12.50 = 260.00; IGV 18 % = 46.80; total = 306.80
        $this->assertSame('260.00', number_format((float) $venta->subtotal, 2, '.', ''));
        $this->assertSame('46.80', number_format((float) $venta->igv, 2, '.', ''));
        $this->assertSame('306.80', number_format((float) $venta->total, 2, '.', ''));
        $this->assertSame(2, $venta->detalles()->count());
        $this->assertSame(50, $bisagra->fresh()->stock);
        $this->assertSame(16, $corredera->fresh()->stock);
    }

    public function test_el_precio_de_la_venta_no_cambia_si_luego_cambia_el_producto(): void
    {
        $producto = $this->producto('BIS-2', 10, 4.20);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 2]]])
            ->assertSessionHasNoErrors();

        $producto->update(['precio_venta' => 9.99]);

        $detalle = VentaDetalle::firstOrFail();
        $this->assertSame('4.20', number_format((float) $detalle->precio_unitario, 2, '.', ''));
        $this->assertSame('8.40', number_format((float) $detalle->subtotal, 2, '.', ''));
    }

    public function test_no_vende_mas_unidades_que_el_stock_y_no_guarda_nada(): void
    {
        $producto = $this->producto('PIS-1', 5, 18.00);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 6]]])
            ->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, VentaDetalle::count());
        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_si_una_linea_falla_se_revierte_toda_la_venta(): void
    {
        $bien = $this->producto('BIS-3', 10, 3.50);
        $mal = $this->producto('BIS-4', 1, 3.50);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $bien->id, 'modalidad' => 'unidad', 'cantidad' => 3],
            ['producto_id' => $mal->id, 'modalidad' => 'unidad', 'cantidad' => 2],
        ]])->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(10, $bien->fresh()->stock);
    }

    public function test_no_vende_productos_sin_precio(): void
    {
        $producto = $this->producto('CER-1', 10, 0);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 1]]])
            ->assertSessionHasErrors('productos');

        $this->assertSame(0, Venta::count());
    }

    public function test_no_acepta_el_mismo_producto_dos_veces(): void
    {
        $producto = $this->producto('BIS-5', 10, 4.20);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 1],
            ['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 2],
        ]])->assertSessionHasErrors('productos.1.producto_id');

        $this->assertSame(0, Venta::count());
    }

    public function test_solo_admin_y_ventas_acceden_al_modulo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get('/ventas')->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/ventas/crear')->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'inventario']))
            ->get('/ventas')->assertForbidden();
    }

    public function test_el_formulario_solo_ofrece_productos_con_stock_y_precio(): void
    {
        $this->producto('CON-STOCK', 5, 4.20);
        $this->producto('SIN-STOCK', 0, 4.20);
        $this->producto('SIN-PRECIO', 5, 0);
        $this->actingAs(User::factory()->create(['role' => 'ventas']))
            ->get('/ventas/crear')
            ->assertOk()
            ->assertSee('CON-STOCK')
            ->assertDontSee('SIN-STOCK')
            ->assertDontSee('SIN-PRECIO');
    }

    public function test_no_se_elimina_un_producto_con_ventas(): void
    {
        $producto = $this->producto('BIS-6', 10, 4.20);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));
        $this->registrar(['productos' => [['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 1]]])
            ->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/productos/{$producto->id}")
            ->assertSessionHas('error');

        $this->assertNotNull(Producto::find($producto->id));
    }

    public function test_vender_por_caja_convierte_las_cajas_a_unidades(): void
    {
        $producto = $this->producto('CAJ-1', 100, 2.00); // caja de 10 unidades
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'caja', 'cantidad' => 3],
        ]])->assertSessionHasNoErrors();

        $detalle = VentaDetalle::firstOrFail();
        $this->assertSame(3, $detalle->cajas);
        $this->assertSame(30, $detalle->cantidad);
        $this->assertSame(10, $detalle->unidades_por_caja);
        $this->assertSame('60.00', number_format((float) $detalle->subtotal, 2, '.', ''));
        $this->assertSame(70, $producto->fresh()->stock);
        $this->assertSame('Por caja (10 u.)', $detalle->descripcionModalidad());
        $this->assertSame('3 cajas', $detalle->descripcionCantidad());
    }

    public function test_vender_por_unidad_no_guarda_cajas(): void
    {
        $producto = $this->producto('UNI-1', 100, 2.00);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 7],
        ]])->assertSessionHasNoErrors();

        $detalle = VentaDetalle::firstOrFail();
        $this->assertNull($detalle->cajas);
        $this->assertSame(7, $detalle->cantidad);
        $this->assertSame(93, $producto->fresh()->stock);
        $this->assertSame('Por unidad', $detalle->descripcionModalidad());
        $this->assertSame('7 unidades', $detalle->descripcionCantidad());
    }

    public function test_una_venta_puede_mezclar_lineas_por_caja_y_por_unidad(): void
    {
        $caja = $this->producto('MIX-1', 100, 1.00);
        $unidad = $this->producto('MIX-2', 100, 3.00);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $caja->id, 'modalidad' => 'caja', 'cantidad' => 2],
            ['producto_id' => $unidad->id, 'modalidad' => 'unidad', 'cantidad' => 5],
        ]])->assertSessionHasNoErrors();

        $venta = Venta::firstOrFail();
        // 2 cajas × 10 × 1.00 = 20.00 y 5 × 3.00 = 15.00; subtotal 35.00, IGV 6.30, total 41.30
        $this->assertSame('35.00', number_format((float) $venta->subtotal, 2, '.', ''));
        $this->assertSame('6.30', number_format((float) $venta->igv, 2, '.', ''));
        $this->assertSame('41.30', number_format((float) $venta->total, 2, '.', ''));
        $this->assertSame(80, $caja->fresh()->stock);
        $this->assertSame(95, $unidad->fresh()->stock);

        $this->get('/ventas/'.$venta->id)->assertOk()
            ->assertSee('Por caja (10 u.)')->assertSee('2 cajas')
            ->assertSee('Por unidad')->assertSee('5 unidades');
    }

    public function test_por_caja_respeta_el_stock_en_unidades(): void
    {
        $producto = $this->producto('STK-1', 25, 1.00); // alcanza para 2 cajas completas
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'caja', 'cantidad' => 3],
        ]])->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(25, $producto->fresh()->stock);

        // Por unidad sí se pueden vender las 25 unidades, incluidas las 5 sueltas.
        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'unidad', 'cantidad' => 25],
        ]])->assertSessionHasNoErrors();

        $this->assertSame(0, $producto->fresh()->stock);
    }

    public function test_exige_una_modalidad_valida(): void
    {
        $producto = $this->producto('MOD-1', 100, 1.00);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'cantidad' => 1],
        ]])->assertSessionHasErrors('productos.0.modalidad');

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'modalidad' => 'docena', 'cantidad' => 1],
        ]])->assertSessionHasErrors('productos.0.modalidad');

        $this->assertSame(0, Venta::count());
    }

    public function test_al_editar_cada_linea_conserva_su_modalidad(): void
    {
        $caja = $this->producto('EDT-1', 100, 1.00);
        $unidad = $this->producto('EDT-2', 100, 1.00);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->registrar(['productos' => [
            ['producto_id' => $caja->id, 'modalidad' => 'caja', 'cantidad' => 4],
            ['producto_id' => $unidad->id, 'modalidad' => 'unidad', 'cantidad' => 9],
        ]])->assertSessionHasNoErrors();

        $html = $this->get('/ventas/'.Venta::firstOrFail()->id.'/editar')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="caja"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/value="unidad"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/name="productos\[0\]\[cantidad\]"[^>]*value="4"/s', $html);
        $this->assertMatchesRegularExpression('/name="productos\[1\]\[cantidad\]"[^>]*value="9"/s', $html);
    }
}
