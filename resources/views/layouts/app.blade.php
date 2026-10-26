<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEKALIX') · Sistema de Gestión de Inventario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @auth
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="{{ asset('css/cekalix.css') }}?v={{ filemtime(public_path('css/cekalix.css')) }}" rel="stylesheet">
    @else
        {{-- La pantalla de inicio de sesión conserva el diseño anterior --}}
        <link href="{{ asset('css/cekalix-clasico.css') }}?v={{ filemtime(public_path('css/cekalix-clasico.css')) }}" rel="stylesheet">
    @endauth
    @yield('styles')
</head>
<body>
    @auth
        @php
            $rol = auth()->user()->role;
            $rolesEtiquetas = [
                'admin' => 'Administrador',
                'inventario' => 'Inventario',
                'ventas' => 'Ventas',
            ];
            $activo = fn (string ...$rutas) => request()->routeIs(...$rutas) ? 'active' : '';
        @endphp

        <div class="app-shell">
            <aside class="offcanvas-md offcanvas-start app-sidebar" tabindex="-1" id="menuLateral" aria-label="Menú principal">
                <div class="offcanvas-header d-md-none">
                    <span class="fw-bold text-white">Menú</span>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar"></button>
                </div>

                <div class="offcanvas-body app-sidebar-cuerpo">
                    <a class="app-brand" href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/logo-cekalix.png') }}" alt="CEKALIX" class="app-logo">
                    </a>

                    <nav class="app-nav">
                        <p class="app-nav-titulo">Principal</p>

                        <a class="app-link {{ $activo('dashboard') }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i><span>Mi panel</span>
                        </a>

                        @if(in_array($rol, ['admin', 'inventario', 'ventas'], true))
                            <p class="app-nav-titulo">Inventario</p>

                            <a class="app-link {{ $activo('productos.*') }}" href="{{ route('productos.index') }}">
                                <i class="bi bi-box-seam"></i><span>Catálogo y stock</span>
                            </a>
                        @endif

                        @if(in_array($rol, ['admin', 'inventario'], true))
                            <a class="app-link {{ $activo('importaciones.*', 'mermas.*') }}" href="{{ route('importaciones.index') }}">
                                <i class="bi bi-truck"></i><span>Recepción</span>
                            </a>
                        @endif

                        @if(in_array($rol, ['admin', 'ventas'], true))
                            <p class="app-nav-titulo">Comercial</p>

                            <a class="app-link {{ $activo('ventas.*') }}" href="{{ route('ventas.index') }}">
                                <i class="bi bi-cart3"></i><span>Ventas</span>
                            </a>
                        @endif

                        @if(in_array($rol, ['admin', 'ventas'], true))
                            <a class="app-link {{ $activo('proveedores.*', 'clientes.*') }}"
                               href="{{ $rol === 'admin' ? route('proveedores.index') : route('clientes.index') }}">
                                <i class="bi bi-people"></i><span>Socios</span>
                            </a>
                        @endif
                    </nav>

                    <div class="app-usuario">
                        <div class="app-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>

                        <div class="app-usuario-datos">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ $rolesEtiquetas[$rol] ?? $rol }}</span>
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="app-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="app-contenido">
                <header class="app-topbar d-md-none">
                    <button class="btn app-menu-btn" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral">
                        <i class="bi bi-list"></i>
                    </button>
                    <img src="{{ asset('images/logo-cekalix.png') }}" alt="CEKALIX" style="height: 32px; width: auto;">
                </header>

                <main class="app-main">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <div class="container-fluid px-3 px-md-4">
            <div class="row">
                <div class="col-12">
                    @yield('content')
                </div>
            </div>
        </div>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
