@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card card-custom shadow">
                <div class="card-body p-5">
                    <img src="{{ asset('images/logo-cekalix.png') }}" alt="Logo CEKALIX" class="login-logo">
                    <p class="text-center text-muted mb-4">Inicio de Sesión</p>

                    @if(session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('login.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-rojo w-100">Iniciar sesión</button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center text-muted small">
                        Usuario de prueba: <strong>admin@cekalix.com</strong> | Contraseña: <strong>password</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
