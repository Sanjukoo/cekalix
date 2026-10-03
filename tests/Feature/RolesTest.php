<?php

namespace Tests\Feature;

use App\Models\AtributoCategoria;
use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\ImportacionDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function producto(): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Bisagras', 'slug' => 'bisagras']);
        AtributoCategoria::create(['categoria_id' => $categoria->id, 'nombre' => 'Acabado']);
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor Bisagras']);

        return Producto::create([
            'codigo' => 'B-001',
            'nombre' => 'Bisagra cierre lento',
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'stock' => 5,
            'unidades_por_caja' => 10,
        ]);
    }

    public function test_los_tres_roles_ven_el_catalogo(): void
    {
        $producto = $this->producto();

        foreach (['admin', 'inventario', 'ventas'] as $role) {
            $usuario = $this->usuario($role);

            $this->actingAs($usuario)->get('/productos')
                ->assertOk()
                ->assertSee('B-001')
                ->assertSee('Proveedor Bisagras');
            $this->actingAs($usuario)->get("/productos/{$producto->id}")->assertOk();
        }
    }

    public function test_ventas_no_ve_botones_de_gestion(): void
    {
        $this->producto();

        $this->actingAs($this->usuario('ventas'))->get('/productos')
            ->assertOk()
            ->assertDontSee('Nuevo Producto')
            ->assertDontSee('Eliminar');

        $this->actingAs($this->usuario('admin'))->get('/productos')
            ->assertOk()
            ->assertSee('Nuevo Producto')
            ->assertSee('Eliminar');
    }

    public function test_admin_ve_el_panel_general(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Productos Registrados')
            ->assertSee('Proveedores Activos');
    }

    public function test_admin_puede_crear_productos_y_ventas_no(): void
    {
        $this->actingAs($this->usuario('admin'))->get('/productos/create')->assertOk();
        $this->actingAs($this->usuario('ventas'))->get('/productos/create')->assertForbidden();
    }

    public function test_editar_producto_guarda_los_atributos_de_la_categoria(): void
    {
        $producto = $this->producto();

        $this->actingAs($this->usuario('inventario'))
            ->put("/productos/{$producto->id}", [
                'codigo' => 'B-001',
                'nombre' => 'Bisagra cierre lento',
                'categoria_id' => $producto->categoria_id,
                'proveedor_id' => $producto->proveedor_id,
                'stock' => 5,
                'unidades_por_caja' => 10,
                'atributo_acabado' => 'Niquelado',
            ])
            ->assertRedirect();

        $this->assertSame(['acabado' => 'Niquelado'], $producto->obtenerAtributos());
    }

    public function test_no_se_elimina_un_producto_con_importaciones(): void
    {
        $producto = $this->producto();
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor X']);
        $importacion = Importacion::create([
            'proveedor_id' => $proveedor->id,
            'numero_factura' => 'F-1',
            'numero_contenedor' => 'C-1',
            'fecha_llegada' => '2026-10-01',
        ]);
        ImportacionDetalle::create([
            'importacion_id' => $importacion->id,
            'producto_id' => $producto->id,
            'cajas_facturadas' => 3,
        ]);

        $this->actingAs($this->usuario('admin'))
            ->delete("/productos/{$producto->id}")
            ->assertSessionHas('error');

        $this->assertModelExists($producto);
    }

    public function test_no_se_elimina_un_proveedor_con_productos(): void
    {
        $producto = $this->producto();

        $this->actingAs($this->usuario('admin'))
            ->delete("/proveedores/{$producto->proveedor_id}")
            ->assertSessionHas('error');

        $this->assertModelExists($producto->proveedor);
    }

    public function test_los_seeders_se_pueden_ejecutar_dos_veces(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, User::count());
        $this->assertSame(4, Categoria::count());
        $this->assertSame(3, Proveedor::count());
        $this->assertSame(12, AtributoCategoria::count());
    }

    public function test_admin_puede_editar_y_desactivar_un_proveedor(): void
    {
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor X', 'activo' => true]);
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->get("/proveedores/{$proveedor->id}/edit")
            ->assertOk()
            ->assertSee('Proveedor X');

        // Sin el checkbox "activo" marcado
        $this->actingAs($admin)->put("/proveedores/{$proveedor->id}", [
            'razon_social' => 'Proveedor Y',
            'identificador_wechat' => 'proveedor_y',
            'correo_electronico' => 'ventas@proveedory.com',
            'pais_origen' => 'China',
        ])->assertSessionHasNoErrors();

        $proveedor->refresh();
        $this->assertSame('Proveedor Y', $proveedor->razon_social);
        $this->assertSame('China', $proveedor->pais_origen);
        $this->assertFalse($proveedor->activo);
    }

    public function test_registrar_proveedor_exige_los_datos_de_contacto(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post('/proveedores', [
            'razon_social' => 'Proveedor Z',
            'correo_electronico' => 'no-es-un-correo',
        ])->assertSessionHasErrors(['identificador_wechat', 'correo_electronico', 'pais_origen']);

        $this->actingAs($admin)->post('/proveedores', [
            'razon_social' => 'Proveedor Z',
            'identificador_wechat' => 'proveedor_z',
            'correo_electronico' => 'ventas@proveedorz.com',
            'pais_origen' => 'Turquía',
            'activo' => '1',
        ])->assertRedirect('/proveedores');

        $this->assertDatabaseHas('proveedores', [
            'razon_social' => 'Proveedor Z',
            'identificador_wechat' => 'proveedor_z',
            'correo_electronico' => 'ventas@proveedorz.com',
            'pais_origen' => 'Turquía',
            'activo' => true,
        ]);
    }
}
