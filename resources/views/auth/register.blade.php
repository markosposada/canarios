@extends('layouts.app')

@section('title', 'Registrar Usuario')

@section('content')
<div class="register-form">
    <h2 class="mb-4 text-center">Registro de Usuarios</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Nombres --}}
        <div class="mb-3">
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" name="nombres" class="form-control" value="{{ old('nombres') }}" required autofocus>
            @error('nombres')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Apellidos --}}
        <div class="mb-3">
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" name="apellidos" class="form-control" value="{{ old('apellidos') }}" required>
            @error('apellidos')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Cédula --}}
        <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" name="cedula" class="form-control" value="{{ old('cedula') }}" required>
            @error('cedula')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Celular --}}
        <div class="mb-3">
            <label for="celular" class="form-label">Celular</label>
            <input type="text" name="celular" class="form-control" value="{{ old('celular') }}" required>
            @error('celular')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Rol --}}
        <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select name="rol" class="form-select" required>
                <option value="" disabled {{ old('rol') ? '' : 'selected' }}>Seleccione un rol</option>
                <option value="administrador" {{ old('rol') === 'administrador' ? 'selected' : '' }}>Administrador</option>
                <option value="operadora" {{ old('rol') === 'operadora' ? 'selected' : '' }}>Operadora</option>
                <option value="conductor" {{ old('rol') === 'conductor' ? 'selected' : '' }}>Conductor</option>
            </select>
            @error('rol')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Contraseña --}}
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
            @error('password')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Confirmar Contraseña --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Registrar</button>
    </form>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Modal éxito: se cierra automático y resetea formulario --}}
@if(session('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'success',
        title: '¡Registro exitoso!',
        text: @json(session('success')),
        timer: 1600,              // ⏱️ se cierra solo
        timerProgressBar: true,
        showConfirmButton: false, // ✅ sin botón
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then(() => {
        const form = document.querySelector("form");
        if (form) form.reset();

        const inputNombres = document.querySelector("input[name='nombres']");
        if (inputNombres) inputNombres.focus();
    });
});
</script>
@endif

{{-- Modal error --}}
@if(session('error'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: @json(session('error')),
        confirmButtonText: 'Cerrar'
    });
});
</script>
@endif
@endsection
