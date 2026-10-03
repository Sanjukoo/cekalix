<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEKALIX') - Sistema de Gestión de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/cekalix.css') }}?v={{ filemtime(public_path('css/cekalix.css')) }}" rel="stylesheet">
    @yield('styles')
</head>
<body>
    @auth
        <nav class="navbar navbar-expand-md navbar-custom mb-4">
            <div class="container-fluid px-3 px-md-4">
                <a class="navbar-brand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo-cekalix.png') }}" alt="Logo CEKALIX" class="logo-cekalix">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                {{ Auth::user()->name }}
                                <span class="rol-usuario">{{ Auth::user()->role }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Cerrar sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    @endauth

    <div class="container-fluid px-3 px-md-4">
        <div class="row">
            @auth
                <div class="col-md-3 col-lg-2">
                    <div class="sidebar">
                        <a href="{{ route('dashboard') }}" class="{{request()->routeIs('dashboard') ? 'active' : ''}}">
                            Mi dashboard
                        </a>
                        @if(in_array(auth()->user()->role,['admin', 'inventario', 'ventas'], true))
                            <a href="{{route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : ''}}">
                                Catálogo y Stock
                            </a>
                        @endif

                        @if(auth()->user()->role === 'admin')
                        <a href="{{route('proveedores.index') }}" class="{{request()->routeIs('proveedores.*') ? 'active' : ''}}">
                            Proveedores
                        </a>
                        <a href="{{route('importaciones.index')}}" class="{{request()->routeIs('importaciones.*') ? 'active' : ''}}">
                            Importaciones
                        </a>
                        @endif
                    </div>
                </div>
                <div class="col-md-9 col-lg-10 pb-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            @else
                <div class="col-12">
                    @yield('content')
                </div>
            @endauth
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
