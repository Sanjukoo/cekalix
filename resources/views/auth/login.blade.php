@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card card-custom shadow">
                <div class="card-body p-5">
                    <h3 class="text-center mb-1" style="color: #000000; font-weight: 700;">CEKALIX</h3>
                    <p class="text-center text-muted mb-4">Inicio de Sesión</p>

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('login.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
