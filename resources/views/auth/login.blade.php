<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    {{-- SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div>
            <h1 class="text-center mb-1 fw-bold custom-title"></h1>
            <div class="text-center mb-2">
                <img src="{{ asset('images/canarios.png') }}" alt="Logo Los Canarios" style="max-width: 300px;">
            </div>
            <div class="card shadow" style="width: 24rem;">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Iniciar Sesión</h3>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                        <div class="text-center mt-3">
                            <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-2">
                <a href="{{ route('register') }}">¿No tienes cuenta? Regístrate aquí</a>
            </div>
        </div>
    </div>
    @if(session('login_error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: '{{ session('login_error') }}',
        confirmButtonColor: '#d33',
        confirmButtonText: 'Error, datos ingresados incorrectos',
    });
</script>
@endif

</body>
</html>
