@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('styles')
    <link href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}" rel="stylesheet">
@endsection

@section('content')

<div class="login-page">

    <div class="login-container">

        {{-- =========================
             PANEL IZQUIERDO
        ========================== --}}
        <div class="login-info">

            <div class="login-brand">

                <img
                    src="{{ asset('images/logo-login.png') }}"
                    alt="Logo CEKALIX"
                    class="login-logo"
                >

                <p class="system-label">
                    SISTEMA EMPRESARIAL
                </p>

                <h1 class="login-title">
                    Sistema de
                    <span>Inventario y Ventas</span>
                </h1>

                <p class="login-description">
                    Gestiona productos, inventario, importaciones
                    y ventas B2B desde una sola plataforma.
                </p>

                <div class="login-features">

                    <div class="login-feature">
                        <div class="feature-icon">✓</div>
                        <span>Control de inventario y productos</span>
                    </div>

                    <div class="login-feature">
                        <div class="feature-icon">✓</div>
                        <span>Gestión de importaciones</span>
                    </div>

                    <div class="login-feature">
                        <div class="feature-icon">✓</div>
                        <span>Ventas empresariales B2B</span>
                    </div>

                </div>

            </div>

        </div>


        {{-- =========================
             PANEL DERECHO
        ========================== --}}
        <div class="login-form-container">

            <div class="login-form">

                <p class="system-label">
                    CEKALIX
                </p>

                <h2>Bienvenido</h2>

                <p class="login-subtitle">
                    Ingresa tus credenciales para continuar
                </p>


                {{-- =========================
                     MENSAJE DE ÉXITO
                ========================== --}}
                @if(session('success'))

                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>

                @endif


                {{-- =========================
                     ERRORES
                ========================== --}}
                @if($errors->any())

                    <div class="alert alert-danger" role="alert">

                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach

                    </div>

                @endif


                {{-- =========================
                     FORMULARIO LOGIN
                ========================== --}}
                <form action="{{ route('login.store') }}" method="POST">

                    @csrf


                    {{-- CORREO --}}
                    <div class="mb-4">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="Ingresa tu correo"
                            required
                            autocomplete="username"
                        >

                    </div>


                    {{-- CONTRASEÑA --}}
                    <div class="mb-4">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >

                    </div>


                    {{-- BOTÓN --}}
                    <button
                        type="submit"
                        class="btn btn-login w-100"
                    >
                        Iniciar sesión
                    </button>

                </form>


                <hr class="login-divider">


                {{-- =========================
                     USUARIO DE PRUEBA
                ========================== --}}
                <div class="test-user">

                    <p class="test-user-title">
                        Usuario de prueba
                    </p>

                    <div class="test-user-data">
                        <span>Correo:</span>
                        <strong>admin@cekalix.com</strong>
                    </div>

                    <div class="test-user-data">
                        <span>Contraseña:</span>
                        <strong>password</strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection