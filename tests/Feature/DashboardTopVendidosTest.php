<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTopVendidosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function producto(string $codigo): Producto
    {
        $categoria = Categoria::firstOrCreate(['slug' => 'bisagras'], ['nombre' => 'Bisagras']);
        $proveedor = Proveedor::firstOrCreate(['razon_social' => 'Proveedor de prueba']);

        return Producto::create([
            'codigo' => $codigo,
            'nombre' => 'Producto '.$codigo,
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'stock' => 0,
            'unidades_por_caja' => 1,
            'precio_venta' => 1,
        ]);
    }

    // Crea una venta con una línea por producto: [Producto, unidades].
    private function venta(array $lineas, string $estado = Venta::VIGENTE): void
    {
        $venta = Venta::create([
            'fecha_venta' => '2026-11-20',
            'subtotal' => 0,
            'igv' => 0,
            'total' => 0,
            'estado' => $estado,
            'registrado_por' => User::factory()->create()->id,
        ]);

        foreach ($lineas as [$producto, $unidades]) {
            VentaDetalle::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $unidades,
                'unidades_por_caja' => 1,
                'precio_unitario' => 1,
                'subtotal' => $unidades,
            ]);
        }
    }

    public function test_muestra_los_5_productos_mas_vendidos_por_unidades(): void
    {
        $p = collect(['A', 'B', 'C', 'D', 'E', 'F'])->mapWithKeys(fn ($c) => [$c => $this->producto($c)]);

        $this->venta([[$p['A'], 10], [$p['B'], 40], [$p['C'], 5]]);
        $this->venta([[$p['A'], 25], [$p['D'], 15], [$p['E'], 3], [$p['F'], 2]]);

        // Una venta anulada no cuenta, aunque sea grande.
        $this->venta([[$p['F'], 500]], Venta::ANULADA);

        $top = $this->actingAs($this->admin())->get('/dashboard')
            ->assertOk()
            ->assertSee('Productos más vendidos')
            ->viewData('topVendidos');

        $this->assertSame(['B', 'A', 'D', 'C', 'E'], $top->pluck('codigo')->all());
        $this->assertSame([40, 35, 15, 5, 3], $top->pluck('unidades')->all());

        // Porcentaje sobre las 100 unidades vendidas (vigentes) en total.
        $this->assertSame(40.0, (float) $top[0]['porcentaje']);
    }

    public function test_los_ejes_usan_sku_y_meses_abreviados(): void
    {
        $this->producto('COR-TE45-400');

        $respuesta = $this->actingAs($this->admin())->get('/dashboard')->assertOk();

        // Stock: SKU en el eje y nombre completo aparte para el tooltip.
        $this->assertSame(['COR-TE45-400'], $respuesta->viewData('stockLabels')->all());
        $this->assertSame(['Producto COR-TE45-400'], $respuesta->viewData('stockNombres')->all());

        // Meses de tres letras en los ejes; los completos quedan para el tooltip.
        $this->assertSame('Ene', $respuesta->viewData('mermasLabels')[0]);
        $this->assertSame('Septiembre', $respuesta->viewData('mesesCompletos')[8]);
    }

    public function test_sin_ventas_muestra_un_aviso_en_lugar_del_grafico(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')
            ->assertOk()
            ->assertSee('Aún no hay ventas registradas.')
            ->assertDontSee('id="graficoTopVendidos"', false);
    }
}
