<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CEKALIX') - Sistema de Gestión de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #000000;
            --danger-color: #C41C3B;
            --dark-color: #1a1a1a;
            --light-input: #E3F2FD;
            --success-color: #A5D6A7;
        }

        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .navbar-custom {
            background-color: #000000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .logo-cekalix {
            height: 45px;
            width: auto;
            display: block;
        }

        .btn-rojo {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-rojo:hover {
            background-color: #A01729;
            color: white;
        }

        .btn-negro {
            background-color: #000000;
            color: white;
            border: none;
        }

        .btn-negro:hover {
            background-color: #2a2a2a;
            color: white;
        }

        .card-custom {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }

        .card-header {
            background-color: #000000;
            color: white;
            border: none;
            font-weight: 600;
        }

        .form-control {
            background-color: var(--light-input);
            border: 1px solid #B3E5FC;
            color: #333;
        }

        .form-control:focus {
            background-color: var(--light-input);
            border-color: #81D4FA;
            box-shadow: 0 0 0 0.2rem rgba(129, 212, 250, 0.25);
        }

        .form-select {
            background-color: var(--light-input);
            border: 1px solid #B3E5FC;
            color: #333;
        }

        .form-select:focus {
            background-color: var(--light-input);
            border-color: #81D4FA;
            box-shadow: 0 0 0 0.2rem rgba(129, 212, 250, 0.25);
        }

        .sidebar {
            background-color: white;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .sidebar a {
            color: #333;
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: block;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #E3F2FD;
            border-left-color: #000000;
            color: #000000;
        }

        .alert-success {
            background-color: var(--success-color);
            color: #2E7D32;
            border: 1px solid #81C784;
        }

        .alert-danger {
            background-color: #FFCDD2;
            color: #C62828;
            border: 1px solid #EF9A9A;
        }

        .badge-sku {
            background-color: #E3F2FD;
            color: #01579B;
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
        }

        .badge-estado {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
        }

        .badge-estado.en-recepcion {
            background-color: #FFF9C4;
            color: #F57F17;
        }

        .badge-estado.procesada {
            background-color: var(--success-color);
            color: #2E7D32;
        }

        .form-label {
            color: #333;
            font-weight: 500;
        }

        .text-muted {
            color: #757575 !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-top: 1rem;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-custom mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-cekalix.jpg') }}" 
                alt="Logo CEKALIX" 
                class="logo-cekalix">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Cerrar sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item"><a class="nav-link text-white" href="{{ route('login') }}">Iniciar sesión</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            @auth
                <div class="col-md-3 col-lg-2">
                    <div class="sidebar">
                        <a href="{{ route('dashboard') }}" class="@if(request()->routeIs('dashboard')) active @endif">
                            Dashboard
                        </a>
                        <a href="{{ route('productos.index') }}" class="@if(request()->routeIs('productos.*')) active @endif">
                            Productos
                        </a>
                        <a href="{{ route('proveedores.index') }}" class="@if(request()->routeIs('proveedores.*')) active @endif">
                            Proveedores
                        </a>
                        <a href="{{ route('importaciones.index') }}" class="@if(request()->routeIs('importaciones.*')) active @endif">
                            Importaciones
                        </a>
                    </div>
                </div>
                <div class="col-md-9 col-lg-10">
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
