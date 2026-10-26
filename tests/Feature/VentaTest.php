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
                ['producto_id' => $bisagra->id, 'cantidad' => 50],
                ['producto_id' => $corredera->id, 'cantidad' => 4],
            ],
        ])->assertSessionHasNoErrors();

        $venta = Venta::firstOrFail();

        $this->assertSame($cliente->id, $venta->cliente_id);
        // 50 × 4.20 + 4 × 12.50 = 260.00
        $this->assertSame('260.00', number_format((float) $venta->total, 2, '.', ''));
        $this->assertSame(2, $venta->detalles()->count());
        $this->assertSame(50, $bisagra->fresh()->stock);
        $this->assertSame(16, $corredera->fresh()->stock);
    }

    public function test_el_precio_de_la_venta_no_cambia_si_luego_cambia_el_producto(): void
    {
        $producto = $this->producto('BIS-2', 10, 4.20);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'cantidad' => 2]]])
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

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'cantidad' => 6]]])
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
            ['producto_id' => $bien->id, 'cantidad' => 3],
            ['producto_id' => $mal->id, 'cantidad' => 2],
        ]])->assertSessionHasErrors('stock');

        $this->assertSame(0, Venta::count());
        $this->assertSame(10, $bien->fresh()->stock);
    }

    public function test_no_vende_productos_sin_precio(): void
    {
        $producto = $this->producto('CER-1', 10, 0);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [['producto_id' => $producto->id, 'cantidad' => 1]]])
            ->assertSessionHasErrors('productos');

        $this->assertSame(0, Venta::count());
    }

    public function test_no_acepta_el_mismo_producto_dos_veces(): void
    {
        $producto = $this->producto('BIS-5', 10, 4.20);
        $this->actingAs(User::factory()->create(['role' => 'ventas']));

        $this->registrar(['productos' => [
            ['producto_id' => $producto->id, 'cantidad' => 1],
            ['producto_id' => $producto->id, 'cantidad' => 2],
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
        $this->registrar(['productos' => [['producto_id' => $producto->id, 'cantidad' => 1]]])
            ->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/productos/{$producto->id}")
            ->assertSessionHas('error');

        $this->assertNotNull(Producto::find($producto->id));
    }
}
