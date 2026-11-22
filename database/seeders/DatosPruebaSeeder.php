<?php

namespace Database\Seeders;

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConteoFisicoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\MermaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\VentaController;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Importacion;
use App\Models\ImportacionDetalle;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| DATOS DE PRUEBA REALISTAS (agosto a noviembre de 2026)
|--------------------------------------------------------------------------
| Simula cuatro meses de trabajo de CEKALIX pasando cada operación por los
| mismos controladores que usa el sistema: validaciones, kárdex, IGV, cajas,
| mermas y cierre de recepción. Cada operación la hace el usuario del rol
| que corresponde y con la fecha y hora en que habría ocurrido.
|
| Ejecutar solo sobre un sistema vacío:
|   php artisan db:seed --class=DatosPruebaSeeder
*/
class DatosPruebaSeeder extends Seeder
{
    private const ZONA = 'America/Lima';

    private User $admin;
    private User $inventario;
    private User $vendedor;

    /** Ventas planificadas que luego se anulan o editan, por clave. */
    private array $ventasMarcadas = [];

    /** Productos que solo se venden con las ventas planificadas (stock bajo y agotado). */
    private const RESERVADOS = ['CER-COR-60', 'TIR-ZA96-OC'];

    public function run(): void
    {
        if (Producto::exists() || Venta::exists() || Importacion::exists()) {
            $this->command?->error('El sistema ya tiene datos. Este seeder solo se ejecuta sobre un sistema vacío.');

            return;
        }

        // Los mensajes de éxito/error de los controladores se leen de una sesión en memoria.
        config(['session.driver' => 'array']);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->inventario = User::where('role', 'inventario')->firstOrFail();
        $this->vendedor = User::where('role', 'ventas')->firstOrFail();

        mt_srand(20261122);

        $eventos = array_merge($this->eventosPlanificados(), $this->ventasDelDia());
        usort($eventos, fn ($a, $b) => strcmp($a[0], $b[0]));

        try {
            foreach ($eventos as [$momento, $paso, $accion]) {
                Carbon::setTestNow(Carbon::parse($momento, self::ZONA)->utc());
                $accion();
                $this->command?->line("  <fg=gray>{$momento}</> {$paso}");
            }
        } finally {
            Carbon::setTestNow();
            Auth::forgetUser();
        }

        $this->resumen();
    }

    /*
    |--------------------------------------------------------------------------
    | Línea de tiempo planificada
    |--------------------------------------------------------------------------
    */
    private function eventosPlanificados(): array
    {
        $e = [];

        // ---------- Configuración inicial (administrador) ----------
        foreach ($this->categorias() as $i => $categoria) {
            $e[] = ["2026-08-01 09:0{$i}", "Categoría {$categoria['nombre']}", fn () => $this->post(
                $this->admin, CategoriaController::class, 'store', $categoria
            )];
        }

        foreach ($this->proveedores() as $i => $proveedor) {
            $e[] = ["2026-08-01 10:0{$i}", "Proveedor {$proveedor['razon_social']}", fn () => $this->post(
                $this->admin, ProveedorController::class, 'store', $proveedor + ['activo' => '1']
            )];
        }

        // ---------- Clientes (ventas) ----------
        foreach ($this->clientes() as $i => [$fecha, $cliente]) {
            $e[] = [$fecha.sprintf(' 11:%02d', $i), "Cliente {$cliente['nombre']}", fn () => $this->post(
                $this->vendedor, ClienteController::class, 'store', $cliente
            )];
        }

        // ---------- Importaciones: registro, productos, conteo, mermas y cierre ----------
        foreach ($this->importaciones() as $imp) {
            $factura = $imp['factura'];

            $e[] = [$imp['llegada'].' 08:30', "Importación {$factura} registrada", fn () => $this->registrarImportacion($imp)];

            // Productos nuevos: el encargado de inventario completa su ficha desde el detalle.
            foreach ($imp['detalles'] as $j => $detalle) {
                if (isset($detalle['producto'])) {
                    $e[] = [$imp['llegada'].sprintf(' 10:%02d', $j), "Producto {$detalle['sku']} registrado", fn () => $this->registrarProducto($factura, $detalle)];
                }
            }

            if (isset($imp['conteo'])) {
                $e[] = [$imp['conteo'].' 11:00', "Conteo físico de {$factura}", fn () => $this->registrarConteo($imp)];
            }

            foreach ($imp['mermas'] ?? [] as $j => $merma) {
                $e[] = [$imp['conteo'].sprintf(' 12:%02d', $j * 10), "Merma de {$merma['sku']} en {$factura}", fn () => $this->registrarMerma($factura, $merma)];
            }

            if (isset($imp['cierre'])) {
                $e[] = [$imp['cierre'].' 09:00', "Cierre de recepción de {$factura}", fn () => $this->post(
                    $this->inventario, ImportacionController::class, 'cerrar', [], [], $this->importacion($factura)
                )];
            }
        }

        // ---------- Correcciones de mermas (solo administrador, importación abierta) ----------
        $e[] = ['2026-10-05 09:15', 'Merma de BIS-REC-95 corregida de 2 a 1 caja', function () {
            $merma = $this->mermaDe('NB-2026-2981', 'BIS-REC-95');
            $this->post($this->admin, MermaController::class, 'update', [
                'importacion_detalle_id' => $merma->importacion_detalle_id,
                'cantidad' => 1,
                'motivo' => 'Cajas aplastadas',
                'fecha_registro' => '2026-10-04',
            ], [], $merma);
        }];

        $e[] = ['2026-10-05 09:30', 'Merma de TIR-AC160-CS eliminada (registrada por error)', fn () => $this->post(
            $this->admin, MermaController::class, 'destroy', [], [], $this->mermaDe('NB-2026-2981', 'TIR-AC160-CS')
        )];

        // ---------- Ajustes manuales de stock (kárdex) ----------
        $ajustes = [
            ['2026-09-01 16:00', 'COR-TE45-400', -1, 'Corredera dañada al reubicar el rack del almacén'],
            ['2026-10-10 17:30', 'BIS-CL35-110', 5, 'Conteo cíclico: bolsa de 5 bisagras sin registrar encontrada en almacén'],
            ['2026-11-05 15:00', 'PIS-GAS100-260', -1, 'Unidad entregada como muestra al cliente Diseños Melamina Perú'],
        ];

        foreach ($ajustes as [$momento, $sku, $cantidad, $motivo]) {
            $e[] = [$momento, "Ajuste de stock {$sku} ({$cantidad})", fn () => $this->post(
                $this->inventario, ProductoController::class, 'ajustar',
                ['cantidad' => $cantidad, 'motivo' => $motivo], [], $this->producto($sku)
            )];
        }

        // ---------- Ediciones de fichas ----------
        $e[] = ['2026-09-20 10:00', 'Proveedor Shenzhen Brillo LED desactivado', function () {
            $proveedor = Proveedor::where('razon_social', 'Shenzhen Brillo LED Co.')->firstOrFail();
            $this->post($this->admin, ProveedorController::class, 'update', [
                'razon_social' => $proveedor->razon_social,
                'identificador_wechat' => $proveedor->identificador_wechat,
                'correo_electronico' => $proveedor->correo_electronico,
                'pais_origen' => $proveedor->pais_origen,
                // Sin "activo": el checkbox desmarcado no se envía.
            ], [], $proveedor);
        }];

        $e[] = ['2026-10-01 08:45', 'Precio de BIS-CL35-110 sube de S/ 4.20 a S/ 4.50', function () {
            $producto = $this->producto('BIS-CL35-110');
            $this->post($this->inventario, ProductoController::class, 'update', [
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'categoria_id' => $producto->categoria_id,
                'proveedor_id' => $producto->proveedor_id,
                'unidades_por_caja' => $producto->unidades_por_caja,
                'precio_venta' => '4.50',
                'descripcion' => $producto->descripcion,
            ] + $this->camposAtributos($producto->obtenerAtributos()), [], $producto);
        }];

        $e[] = ['2026-10-16 12:00', 'Teléfono de Carlos Huamán actualizado', function () {
            $cliente = Cliente::where('nombre', 'Carlos Huamán Quispe')->firstOrFail();
            $this->post($this->vendedor, ClienteController::class, 'update', [
                'nombre' => $cliente->nombre,
                'tipo_documento' => $cliente->tipo_documento,
                'numero_documento' => $cliente->numero_documento,
                'telefono' => '956 210 447',
                'direccion' => $cliente->direccion,
            ], [], $cliente);
        }];

        // ---------- Ventas planificadas: stock bajo, agotado, mixtas, editada y anuladas ----------
        $planificadas = [
            // CER-COR-60 (30 u.) termina con 4 u. → aparece en "stock bajo".
            ['2026-08-25 10:20', 'Constructora Andina S.A.C.', [['CER-COR-60', 'caja', 1]]],
            ['2026-09-18 15:40', 'Diseños Melamina Perú E.I.R.L.', [['CER-COR-60', 'unidad', 6], ['BIS-CL35-165', 'unidad', 12]]],
            ['2026-10-22 11:10', 'Constructora Andina S.A.C.', [['CER-COR-60', 'caja', 1], ['COR-TE45-450', 'unidad', 4]]],
            ['2026-11-17 16:05', 'Mueblería El Roble', [['COR-TE45-400', 'unidad', 6]]],

            // TIR-ZA96-OC (40 u.) se agota → ya no se ofrece en el formulario de venta.
            ['2026-10-12 09:50', 'Diseños Melamina Perú E.I.R.L.', [['TIR-ZA96-OC', 'caja', 2], ['TIR-AC160-CS', 'unidad', 8]]],
            ['2026-10-25 13:30', null, [['TIR-ZA96-OC', 'unidad', 8]]],
            ['2026-11-08 10:45', 'Inversiones Cocinas Modernas S.A.C.', [['TIR-ZA96-OC', 'caja', 1], ['BIS-CL35-110', 'unidad', 10]]],
            ['2026-11-19 17:20', 'Carpintería Hermanos Rojas', [['TIR-ZA96-OC', 'unidad', 2]]],

            // Domingo de hoy: alimenta "Ventas de hoy" del panel.
            ['2026-11-22 10:30', 'Ferretería San Martín E.I.R.L.', [['PIS-GAS100-260', 'unidad', 2], ['BIS-REC-95', 'unidad', 10]]],
        ];

        foreach ($planificadas as [$momento, $cliente, $lineas]) {
            $e[] = [$momento, 'Venta planificada', fn () => $this->vender($cliente, substr($momento, 0, 10), $lineas)];
        }

        // Venta que el cliente canceló al día siguiente.
        $e[] = ['2026-09-09 11:00', 'Venta a Carlos Huamán (luego anulada)', fn () => $this->ventasMarcadas['cancelada'] = $this->vender(
            'Carlos Huamán Quispe', '2026-09-09', [['COR-TE45-450', 'caja', 1], ['PIS-GAS100-260', 'unidad', 2]]
        )];
        $e[] = ['2026-09-10 09:20', 'Venta anulada: el cliente canceló el pedido', fn () => $this->post(
            $this->vendedor, VentaController::class, 'anular',
            ['motivo_anulacion' => 'El cliente canceló el pedido antes del despacho.'], [], $this->ventasMarcadas['cancelada']
        )];

        // Venta registrada dos veces por error.
        $e[] = ['2026-11-12 14:00', 'Venta duplicada (luego anulada)', fn () => $this->ventasMarcadas['duplicada'] = $this->vender(
            'Mueblería El Roble', '2026-11-12', [['BIS-CL35-165', 'unidad', 10]]
        )];
        $e[] = ['2026-11-12 14:25', 'Venta anulada: registrada dos veces', fn () => $this->post(
            $this->vendedor, VentaController::class, 'anular',
            ['motivo_anulacion' => 'Venta registrada dos veces por error; se conserva la anterior.'], [], $this->ventasMarcadas['duplicada']
        )];

        // Venta editada: el cliente amplió el pedido al día siguiente.
        $e[] = ['2026-11-18 10:00', 'Venta a Ferretería San Martín (luego editada)', fn () => $this->ventasMarcadas['ampliada'] = $this->vender(
            'Ferretería San Martín E.I.R.L.', '2026-11-18', [['BIS-CL35-110', 'unidad', 10]]
        )];
        $e[] = ['2026-11-19 09:40', 'Venta editada: 1 caja de bisagras + 6 bisagras rectas', function () {
            $venta = $this->ventasMarcadas['ampliada'];
            $this->post($this->vendedor, VentaController::class, 'update', [
                'cliente_id' => $venta->cliente_id,
                'fecha_venta' => '2026-11-18',
                'productos' => $this->lineas([['BIS-CL35-110', 'caja', 1], ['BIS-REC-95', 'unidad', 6]]),
            ], [], $venta);
        }];

        return $e;
    }

    /*
    |--------------------------------------------------------------------------
    | Ventas del día a día (aleatorias pero reproducibles)
    |--------------------------------------------------------------------------
    | De lunes a sábado; el producto y la cantidad se eligen al momento de la
    | venta según el stock real, así nunca se vende lo que no hay.
    */
    private function ventasDelDia(): array
    {
        $eventos = [];
        $dia = Carbon::parse('2026-08-08');
        $fin = Carbon::parse('2026-11-21');

        while ($dia->lte($fin)) {
            if (!$dia->isSunday()) {
                // Negocio pequeño: la mayoría de días no hay venta; a veces una, rara vez dos.
                $azar = mt_rand(1, 100);
                $cantidad = $azar <= 78 ? 0 : ($azar <= 97 ? 1 : 2);

                for ($i = 0; $i < $cantidad; $i++) {
                    $momento = $dia->format('Y-m-d').sprintf(' %02d:%02d', mt_rand(9, 18), mt_rand(0, 59));
                    $semilla = mt_rand();
                    $fecha = $dia->format('Y-m-d');

                    $eventos[] = [$momento, 'Venta', fn () => $this->ventaAleatoria($fecha, $semilla)];
                }
            }

            $dia->addDay();
        }

        return $eventos;
    }

    private function ventaAleatoria(string $fecha, int $semilla): void
    {
        mt_srand($semilla);

        $disponibles = Producto::where('stock', '>', 0)
            ->whereNotIn('codigo', self::RESERVADOS)
            ->orderBy('codigo')
            ->get();

        if ($disponibles->isEmpty()) {
            return;
        }

        $lineas = [];
        $usados = [];
        $cantidadLineas = min(mt_rand(1, 100) <= 70 ? 1 : 2, $disponibles->count());

        while (count($lineas) < $cantidadLineas) {
            $producto = $disponibles[mt_rand(0, $disponibles->count() - 1)];

            if (isset($usados[$producto->id])) {
                continue;
            }

            $usados[$producto->id] = true;

            // Pedidos chicos: como máximo el 10 % del stock, una caja o hasta 12 unidades.
            $tope = max(1, intdiv($producto->stock * 10, 100));
            $porCaja = mt_rand(1, 100) <= 30 && $producto->unidades_por_caja <= $tope;

            $lineas[] = $porCaja
                ? [$producto->codigo, 'caja', 1]
                : [$producto->codigo, 'unidad', mt_rand(1, min($tope, 12))];
        }

        // Una de cada cuatro ventas es a "Cliente general".
        $clientes = Cliente::where('created_at', '<=', now())->orderBy('id')->pluck('nombre');
        $cliente = mt_rand(1, 100) <= 25 || $clientes->isEmpty()
            ? null
            : $clientes[mt_rand(0, $clientes->count() - 1)];

        $this->vender($cliente, $fecha, $lineas);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones
    |--------------------------------------------------------------------------
    */
    private function registrarImportacion(array $imp): void
    {
        $archivos = [];

        if ($imp['pdf'] ?? false) {
            $archivos['archivo_factura'] = $this->facturaPdf($imp);
        }

        $this->post($this->admin, ImportacionController::class, 'store', [
            'proveedor_id' => Proveedor::where('razon_social', $imp['proveedor'])->value('id'),
            'numero_factura' => $imp['factura'],
            'fecha_llegada' => $imp['llegada'],
            'detalles' => array_map(fn ($d) => [
                'producto_codigo' => $d['sku'],
                'cajas_facturadas' => $d['facturadas'],
            ], $imp['detalles']),
        ], $archivos);
    }

    private function registrarProducto(string $factura, array $detalle): void
    {
        [$nombre, $categoria, $porCaja, $precio, $atributos] = $detalle['producto'];

        $this->post($this->inventario, ProductoController::class, 'store', [
            'importacion_detalle_id' => $this->detalle($factura, $detalle['sku'])->id,
            'nombre' => $nombre,
            'categoria_id' => Categoria::where('nombre', $categoria)->value('id'),
            'unidades_por_caja' => $porCaja,
            'precio_venta' => $precio,
            'descripcion' => "{$nombre}. Presentación de {$porCaja} unidades por caja.",
        ] + $this->camposAtributos($atributos));
    }

    private function registrarConteo(array $imp): void
    {
        $importacion = $this->importacion($imp['factura']);
        $conteos = [];
        $imagenes = [];

        foreach ($imp['detalles'] as $d) {
            $id = $this->detalle($imp['factura'], $d['sku'])->id;
            $conteos[$id] = $d['contadas'];

            if ($d['foto'] ?? false) {
                $imagenes[$id] = $this->foto(
                    "conteo-{$d['sku']}.png",
                    ['EVIDENCIA DE CONTEO FISICO', "Factura {$imp['factura']}", "SKU {$d['sku']}", "Facturadas: {$d['facturadas']}  Contadas: {$d['contadas']}"],
                    [214, 226, 240]
                );
            }
        }

        $this->post($this->inventario, ConteoFisicoController::class, 'update',
            ['conteos' => $conteos], ['imagenes' => $imagenes], $importacion);
    }

    private function registrarMerma(string $factura, array $merma): void
    {
        $archivos = ($merma['foto'] ?? false)
            ? ['imagen' => $this->foto("merma-{$merma['sku']}.png", ['EVIDENCIA DE MERMA', "Factura {$factura}", "SKU {$merma['sku']}", "{$merma['cantidad']} caja(s) danada(s)"], [245, 222, 214])]
            : [];

        $this->post($this->inventario, MermaController::class, 'store', [
            'importacion_detalle_id' => $this->detalle($factura, $merma['sku'])->id,
            'cantidad' => $merma['cantidad'],
            'motivo' => $merma['motivo'],
            'descripcion' => $merma['descripcion'] ?? null,
            'fecha_registro' => now(self::ZONA)->toDateString(),
        ], $archivos);
    }

    // Registra la venta y devuelve exactamente la venta creada.
    private function vender(?string $cliente, string $fecha, array $lineas): Venta
    {
        $this->post($this->vendedor, VentaController::class, 'store', [
            'cliente_id' => $cliente ? Cliente::where('nombre', $cliente)->value('id') : null,
            'fecha_venta' => $fecha,
            'productos' => $this->lineas($lineas),
        ]);

        return Venta::latest('id')->firstOrFail();
    }

    private function lineas(array $lineas): array
    {
        return array_map(fn ($l) => [
            'producto_id' => $this->producto($l[0])->id,
            'modalidad' => $l[1],
            'cantidad' => $l[2],
        ], $lineas);
    }

    /*
    |--------------------------------------------------------------------------
    | Ejecución de un controlador como lo haría el usuario
    |--------------------------------------------------------------------------
    */
    private function post(User $usuario, string $controlador, string $metodo, array $datos, array $archivos = [], ...$modelos): void
    {
        Auth::setUser($usuario);

        $request = Request::create('/', 'POST', $datos, [], $archivos);
        $request->setUserResolver(fn () => $usuario);
        app()->instance('request', $request);

        $sesion = app('session.store');
        $sesion->flush();
        $request->setLaravelSession($sesion);

        try {
            // cerrar() y destroy() no reciben la petición, solo el modelo.
            $parametros = (new \ReflectionMethod($controlador, $metodo))->getParameters();
            $recibeRequest = ($parametros[0] ?? null)?->getType()?->getName() === Request::class;

            $respuesta = $recibeRequest
                ? app($controlador)->{$metodo}($request, ...$modelos)
                : app($controlador)->{$metodo}(...$modelos);
        } catch (ValidationException $e) {
            throw new RuntimeException($this->fallo($controlador, $metodo, $e->errors()));
        }

        // Algunos controladores devuelven el error como redirección con mensaje.
        $errores = $sesion->get('errors')?->all() ?? [];
        if ($sesion->get('error')) {
            $errores[] = $sesion->get('error');
        }

        if ($errores || !$respuesta instanceof RedirectResponse) {
            throw new RuntimeException($this->fallo($controlador, $metodo, $errores));
        }
    }

    private function fallo(string $controlador, string $metodo, array $errores): string
    {
        return 'Falló '.class_basename($controlador)."@{$metodo} el ".now(self::ZONA)->format('d/m/Y H:i').': '
            .json_encode($errores, JSON_UNESCAPED_UNICODE);
    }

    /*
    |--------------------------------------------------------------------------
    | Búsquedas
    |--------------------------------------------------------------------------
    */
    private function importacion(string $factura): Importacion
    {
        return Importacion::where('numero_factura', $factura)->firstOrFail();
    }

    private function detalle(string $factura, string $sku): ImportacionDetalle
    {
        return $this->importacion($factura)->detalles()->where('producto_codigo', $sku)->firstOrFail();
    }

    private function mermaDe(string $factura, string $sku): Merma
    {
        return Merma::where('importacion_detalle_id', $this->detalle($factura, $sku)->id)->latest('id')->firstOrFail();
    }

    private function producto(string $sku): Producto
    {
        return Producto::where('codigo', $sku)->firstOrFail();
    }

    /** Convierte ['Longitud' => '400 mm'] en ['atributo_longitud' => '400 mm']. */
    private function camposAtributos(array $atributos): array
    {
        $campos = [];

        foreach ($atributos as $nombre => $valor) {
            $campos['atributo_'.\Illuminate\Support\Str::slug($nombre)] = $valor;
        }

        return $campos;
    }

    /*
    |--------------------------------------------------------------------------
    | Archivos adjuntos (imágenes y facturas PDF legibles)
    |--------------------------------------------------------------------------
    */
    private function foto(string $nombre, array $lineas, array $fondo): UploadedFile
    {
        $imagen = imagecreatetruecolor(640, 400);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, ...$fondo));

        $tinta = imagecolorallocate($imagen, 31, 35, 40);
        $marco = imagecolorallocate($imagen, 200, 16, 46);
        imagerectangle($imagen, 10, 10, 629, 389, $marco);

        foreach ($lineas as $i => $linea) {
            imagestring($imagen, 5, 40, 120 + $i * 34, $linea, $tinta);
        }

        imagestring($imagen, 3, 40, 350, 'CEKALIX - '.now(self::ZONA)->format('d/m/Y H:i'), $tinta);

        $ruta = tempnam(sys_get_temp_dir(), 'ckx').'.png';
        imagepng($imagen, $ruta);
        imagedestroy($imagen);

        return new UploadedFile($ruta, $nombre, 'image/png', null, true);
    }

    private function facturaPdf(array $imp): UploadedFile
    {
        $lineas = [
            'COMMERCIAL INVOICE / FACTURA COMERCIAL',
            '',
            'Proveedor: '.$this->ascii($imp['proveedor']),
            'Factura N.: '.$imp['factura'],
            'Fecha: '.Carbon::parse($imp['llegada'])->subDays(30)->format('d/m/Y'),
            'Cliente: CEKALIX - Lima, Peru',
            '',
            'SKU                    CAJAS',
            '------------------------------',
        ];

        foreach ($imp['detalles'] as $d) {
            $lineas[] = str_pad($d['sku'], 23).$d['facturadas'];
        }

        $lineas[] = '------------------------------';
        $lineas[] = 'Total de cajas: '.array_sum(array_column($imp['detalles'], 'facturadas'));

        $texto = "BT /F1 12 Tf 60 780 Td 18 TL\n";
        foreach ($lineas as $linea) {
            $texto .= '('.addcslashes($linea, '()\\').") Tj T*\n";
        }
        $texto .= 'ET';

        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($texto)." >>\nstream\n{$texto}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
        ];

        $pdf = "%PDF-1.4\n";
        $posiciones = [];

        foreach ($objetos as $i => $objeto) {
            $posiciones[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$objeto}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objetos) + 1)."\n0000000000 65535 f \n";
        foreach ($posiciones as $posicion) {
            $pdf .= sprintf("%010d 00000 n \n", $posicion);
        }
        $pdf .= 'trailer << /Size '.(count($objetos) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        $ruta = tempnam(sys_get_temp_dir(), 'ckx').'.pdf';
        file_put_contents($ruta, $pdf);

        return new UploadedFile($ruta, "factura-{$imp['factura']}.pdf", 'application/pdf', null, true);
    }

    private function ascii(string $texto): string
    {
        return \Illuminate\Support\Str::ascii($texto);
    }

    /*
    |--------------------------------------------------------------------------
    | Catálogo de datos
    |--------------------------------------------------------------------------
    */
    private function categorias(): array
    {
        $categorias = [
            ['Correderas', 'Correderas telescópicas y ocultas para cajones y muebles', [
                'Longitud' => '300 mm, 350 mm, 400 mm, 450 mm, 500 mm',
                'Ancho' => '35 mm, 45 mm',
                'Color' => 'Zincado, Negro, Blanco',
            ]],
            ['Bisagras', 'Bisagras de cazoleta y rectas para puertas de mueble', [
                'Tipo' => 'Recta 95°, Cierre lento 110°, Apertura 165°',
                'Acabado' => 'Niquelado, Negro mate',
            ]],
            ['Pistones', 'Pistones a gas para puertas abatibles', [
                'Fuerza' => '80 N, 100 N, 120 N',
                'Longitud' => '260 mm, 270 mm',
            ]],
            ['Cerraduras', 'Cerraduras de cilindro y para vidrio', [
                'Material' => 'Aleación de zinc, Acero inoxidable',
                'Tamaño' => '22 mm, 60 mm',
            ]],
            ['Tiradores', 'Tiradores y jaladores decorativos para muebles', [
                'Material' => 'Aluminio, Acero inoxidable, Zamak',
                'Medida' => '96 mm, 128 mm, 160 mm',
                'Acabado' => 'Negro mate, Cromo satinado, Oro cepillado',
                'Observación' => '',
            ]],
        ];

        return array_map(fn ($c) => [
            'nombre' => $c[0],
            'descripcion' => $c[1],
            'atributos' => array_map(
                fn ($nombre, $opciones) => ['nombre' => $nombre, 'opciones' => $opciones],
                array_keys($c[2]),
                $c[2]
            ),
        ], $categorias);
    }

    private function proveedores(): array
    {
        return [
            ['razon_social' => 'Proveedor Internacional A.', 'identificador_wechat' => 'prov_internacional_a', 'correo_electronico' => 'ventas@proveedorinternacional.com', 'pais_origen' => 'China'],
            ['razon_social' => 'Global Trade Corp.', 'identificador_wechat' => 'globaltrade_corp', 'correo_electronico' => 'contacto@globaltradecorp.com', 'pais_origen' => 'China'],
            ['razon_social' => 'Importadora del Pacífico', 'identificador_wechat' => 'importadora_pacifico', 'correo_electronico' => 'info@importadoradelpacifico.com', 'pais_origen' => 'Taiwán'],
            ['razon_social' => 'Ningbo Hardware Co., Ltd.', 'identificador_wechat' => 'ningbo_hw_sales', 'correo_electronico' => 'sales@ningbohardware.com', 'pais_origen' => 'China'],
            ['razon_social' => 'Shenzhen Brillo LED Co.', 'identificador_wechat' => 'szbrillo_led', 'correo_electronico' => 'export@szbrilloled.com', 'pais_origen' => 'China'],
        ];
    }

    private function clientes(): array
    {
        return [
            ['2026-08-03', ['nombre' => 'Mueblería El Roble', 'tipo_documento' => 'RUC', 'numero_documento' => '20999100011', 'telefono' => '987 654 321', 'direccion' => 'Av. Los Carpinteros 123, Villa El Salvador, Lima']],
            ['2026-08-03', ['nombre' => 'Carlos Huamán Quispe', 'tipo_documento' => 'DNI', 'numero_documento' => '70990011', 'telefono' => '912 345 678', 'direccion' => 'Jr. Las Flores 456, San Juan de Lurigancho, Lima']],
            ['2026-08-03', ['nombre' => 'Constructora Andina S.A.C.', 'tipo_documento' => 'RUC', 'numero_documento' => '20999100022', 'telefono' => '01 456 7890', 'direccion' => 'Calle Industrial 789, Callao']],
            ['2026-08-04', ['nombre' => 'Carpintería Hermanos Rojas', 'tipo_documento' => 'RUC', 'numero_documento' => '10999100033', 'telefono' => '945 112 908', 'direccion' => 'Av. Industrial 2210, Ate, Lima']],
            ['2026-08-04', ['nombre' => 'Diseños Melamina Perú E.I.R.L.', 'tipo_documento' => 'RUC', 'numero_documento' => '20999100044', 'telefono' => '933 870 215', 'direccion' => 'Av. Túpac Amaru 3480, Comas, Lima']],
            ['2026-10-15', ['nombre' => 'María Fernández Torres', 'tipo_documento' => 'DNI', 'numero_documento' => '70990055', 'telefono' => '978 403 162', 'direccion' => 'Calle Los Cedros 210, Surco, Lima']],
            ['2026-10-15', ['nombre' => 'Inversiones Cocinas Modernas S.A.C.', 'tipo_documento' => 'RUC', 'numero_documento' => '20999100066', 'telefono' => '01 719 3344', 'direccion' => 'Av. Primavera 1150, San Borja, Lima']],
            ['2026-10-15', ['nombre' => 'Ferretería San Martín E.I.R.L.', 'tipo_documento' => 'RUC', 'numero_documento' => '20999100077', 'telefono' => '964 551 230', 'direccion' => 'Jr. San Martín 845, Huancayo, Junín']],
        ];
    }

    /*
     * Cada SKU: facturadas, contadas, foto del conteo y, si es nuevo, su ficha:
     * [nombre, categoría, unidades por caja, precio de venta por unidad, atributos].
     */
    private function importaciones(): array
    {
        return [
            [
                'proveedor' => 'Proveedor Internacional A.', 'factura' => 'JH-2026-0815', 'pdf' => true,
                'llegada' => '2026-08-05', 'conteo' => '2026-08-06', 'cierre' => '2026-08-07',
                'detalles' => [
                    ['sku' => 'COR-TE45-400', 'facturadas' => 12, 'contadas' => 12, 'foto' => true,
                        'producto' => ['Corredera telescópica 45 mm x 400 mm', 'Correderas', 10, '12.50', ['Longitud' => '400 mm', 'Ancho' => '45 mm', 'Color' => 'Zincado']]],
                    ['sku' => 'BIS-CL35-110', 'facturadas' => 10, 'contadas' => 10,
                        'producto' => ['Bisagra cazoleta 35 mm cierre lento 110°', 'Bisagras', 25, '4.20', ['Tipo' => 'Cierre lento 110°', 'Acabado' => 'Niquelado']]],
                    ['sku' => 'PIS-GAS100-260', 'facturadas' => 8, 'contadas' => 8,
                        'producto' => ['Pistón a gas 100 N de 260 mm', 'Pistones', 10, '18.00', ['Fuerza' => '100 N', 'Longitud' => '260 mm']]],
                ],
                'mermas' => [
                    ['sku' => 'COR-TE45-400', 'cantidad' => 1, 'motivo' => 'Cajas aplastadas', 'foto' => true],
                ],
            ],
            [
                'proveedor' => 'Global Trade Corp.', 'factura' => 'GT-2026-0902', 'pdf' => true,
                'llegada' => '2026-08-20', 'conteo' => '2026-08-21', 'cierre' => '2026-08-22',
                'detalles' => [
                    ['sku' => 'COR-TE45-450', 'facturadas' => 10, 'contadas' => 10,
                        'producto' => ['Corredera telescópica 45 mm x 450 mm', 'Correderas', 10, '13.80', ['Longitud' => '450 mm', 'Ancho' => '45 mm', 'Color' => 'Zincado']]],
                    ['sku' => 'BIS-CL35-165', 'facturadas' => 8, 'contadas' => 7, 'foto' => true,
                        'producto' => ['Bisagra cazoleta 35 mm apertura 165°', 'Bisagras', 25, '5.60', ['Tipo' => 'Apertura 165°', 'Acabado' => 'Niquelado']]],
                    ['sku' => 'CER-MUE22-138', 'facturadas' => 6, 'contadas' => 7,
                        'producto' => ['Cerradura para mueble de cilindro 22 mm', 'Cerraduras', 12, '9.80', ['Material' => 'Aleación de zinc', 'Tamaño' => '22 mm']]],
                    ['sku' => 'CER-COR-60', 'facturadas' => 4, 'contadas' => 4,
                        'producto' => ['Cerradura para puerta corrediza de vidrio', 'Cerraduras', 10, '35.00', ['Material' => 'Acero inoxidable', 'Tamaño' => '60 mm']]],
                ],
                'mermas' => [
                    ['sku' => 'BIS-CL35-165', 'cantidad' => 1, 'motivo' => 'Cajas rotas'],
                    ['sku' => 'CER-COR-60', 'cantidad' => 1, 'motivo' => 'OTRO', 'descripcion' => 'Caja con cilindros oxidados por humedad en el contenedor', 'foto' => true],
                ],
            ],
            [
                'proveedor' => 'Ningbo Hardware Co., Ltd.', 'factura' => 'NB-2026-2981', 'pdf' => true,
                'llegada' => '2026-10-03', 'conteo' => '2026-10-04', 'cierre' => '2026-10-06',
                'detalles' => [
                    ['sku' => 'BIS-REC-95', 'facturadas' => 8, 'contadas' => 8,
                        'producto' => ['Bisagra recta 95° para puerta de mueble', 'Bisagras', 25, '3.50', ['Tipo' => 'Recta 95°', 'Acabado' => 'Niquelado']]],
                    ['sku' => 'TIR-AC160-CS', 'facturadas' => 6, 'contadas' => 6,
                        'producto' => ['Tirador de acero 160 mm cromo satinado', 'Tiradores', 20, '8.90', ['Material' => 'Acero inoxidable', 'Medida' => '160 mm', 'Acabado' => 'Cromo satinado']]],
                    ['sku' => 'TIR-ZA96-OC', 'facturadas' => 5, 'contadas' => 5, 'foto' => true,
                        'producto' => ['Tirador de zamak 96 mm oro cepillado', 'Tiradores', 10, '7.20', ['Material' => 'Zamak', 'Medida' => '96 mm', 'Acabado' => 'Oro cepillado', 'Observación' => 'Edición limitada']]],
                    ['sku' => 'TIR-AL128-NM', 'facturadas' => 6, 'contadas' => 6,
                        'producto' => ['Tirador de aluminio 128 mm negro mate', 'Tiradores', 20, '6.50', ['Material' => 'Aluminio', 'Medida' => '128 mm', 'Acabado' => 'Negro mate']]],
                ],
                'mermas' => [
                    ['sku' => 'TIR-ZA96-OC', 'cantidad' => 1, 'motivo' => 'Cajas mojadas', 'foto' => true],
                    ['sku' => 'BIS-REC-95', 'cantidad' => 2, 'motivo' => 'Cajas aplastadas'],
                    // Registrada por error; el administrador la elimina al día siguiente.
                    ['sku' => 'TIR-AC160-CS', 'cantidad' => 1, 'motivo' => 'Cajas rotas'],
                ],
            ],
            [
                // Contada y con merma, lista para cerrar.
                'proveedor' => 'Global Trade Corp.', 'factura' => 'GT-2026-1188',
                'llegada' => '2026-11-14', 'conteo' => '2026-11-15',
                'detalles' => [
                    ['sku' => 'CER-MUE22-138', 'facturadas' => 5, 'contadas' => 5],
                    ['sku' => 'BIS-CL35-165', 'facturadas' => 6, 'contadas' => 6],
                ],
                'mermas' => [
                    ['sku' => 'BIS-CL35-165', 'cantidad' => 1, 'motivo' => 'Cajas mojadas', 'foto' => true],
                ],
            ],
            [
                // Recién llegada: sin conteo y con un SKU nuevo pendiente de registrar como producto.
                'proveedor' => 'Ningbo Hardware Co., Ltd.', 'factura' => 'NB-2026-3307', 'pdf' => true,
                'llegada' => '2026-11-20',
                'detalles' => [
                    ['sku' => 'TIR-AL128-NM', 'facturadas' => 4, 'contadas' => null],
                    ['sku' => 'COR-OC-350', 'facturadas' => 5, 'contadas' => null],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Comprobación final: el stock de cada producto cuadra con su kárdex
    |--------------------------------------------------------------------------
    */
    private function resumen(): void
    {
        $this->command?->newLine();
        $this->command?->info('Datos de prueba cargados.');

        $filas = Producto::orderBy('codigo')->get()->map(function (Producto $p) {
            $kardex = (int) $p->movimientos()->sum('cantidad');
            $vendidas = (int) $p->ventaDetalles()->whereHas('venta', fn ($v) => $v->vigente())->sum('cantidad');

            return [$p->codigo, $p->stock, $kardex, $vendidas, $p->stock === $kardex ? 'OK' : 'NO CUADRA'];
        });

        $this->command?->table(['SKU', 'Stock', 'Suma kárdex', 'Vendidas', 'Cuadra'], $filas->all());

        $this->command?->line(sprintf(
            'Ventas: %d (%d anuladas) · Clientes: %d · Importaciones: %d · Mermas: %d',
            Venta::count(),
            Venta::where('estado', Venta::ANULADA)->count(),
            Cliente::count(),
            Importacion::count(),
            Merma::count()
        ));
    }
}
