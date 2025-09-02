<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Bootstrap (opcional para estilos bonitos) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f4f6f9;
        }
        .register-form {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="register-form">
    <h2 class="mb-4 text-center">Registro de Usuario</h2>

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
                <option value="" disabled selected>Seleccione un rol</option>
                <option value="administrador">Administrador</option>
                <option value="operadora">Operadora</option>
                <option value="conductor">Conductor</option>                
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

{{-- Mostrar popup de éxito --}}
@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: '¡Registro exitoso!',
        text: '{{ session('success') }}',
        confirmButtonText: 'Agregar otro usuario',
        confirmButtonColor: '#3085d6'
    }).then(() => {
        document.querySelector("form").reset();
        document.querySelector("input[name='nombres']").focus();
    });
</script>
@endif

{{-- Mostrar errores generales --}}
@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}'
    });
</script>
@endif

</body>
</html>
