<!DOCTYPE html>
<html lang="es">
<head>
  <link rel="apple-touch-icon" href="/icons/icon-192.png">

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Iniciar Sesión</title>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Tu CSS (si lo usas) -->
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0d6efd">


  <style>
    /* Fondo suave */
    body {
      background: radial-gradient(1200px 600px at 20% 10%, rgba(13,110,253,.12), transparent 60%),
                  radial-gradient(900px 500px at 90% 20%, rgba(25,135,84,.10), transparent 55%),
                  #f8f9fa;
    }

    /* Contenedor centrado + buen padding en móvil */
    .auth-wrap {
      min-height: 100vh;
      padding: 16px;
      padding-top: max(16px, env(safe-area-inset-top));
      padding-bottom: max(16px, env(safe-area-inset-bottom));
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Card responsive: no ancho fijo */
    .auth-card {
      width: 100%;
      max-width: 420px;
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 16px;
      overflow: hidden;
    }

    /* Logo responsive */
    .brand-logo {
      width: min(240px, 70vw);
      height: auto;
    }

    /* Título adaptativo */
    .auth-title {
      font-weight: 800;
      letter-spacing: .2px;
      margin: 0;
    }

    /* Inputs un poquito más altos */
    .form-control {
      padding-top: .75rem;
      padding-bottom: .75rem;
      border-radius: 12px;
    }

    /* Botón con buena altura en móvil */
    .btn-auth {
      padding: .8rem 1rem;
      border-radius: 12px;
      font-weight: 700;
    }

    /* Links más cómodos al tacto */
    .auth-links a {
      text-decoration: none;
    }
    .auth-links a:hover {
      text-decoration: underline;
    }

    /* Espaciado en pantallas muy pequeñas */
    @media (max-width: 360px) {
      .card-body { padding: 18px !important; }
    }
  </style>
</head>

<body>
  <div class="auth-wrap">
    <div class="w-100" style="max-width: 420px;">

      <!-- Logo -->
      <div class="text-center mb-3">
        <img src="{{ asset('images/canarios.png') }}" alt="Logo Los Canarios" class="brand-logo">
      </div>

      <!-- Card -->
      <div class="card shadow-sm auth-card">
        <div class="card-body p-4 p-sm-4">
          <div class="text-center mb-3">
            <h3 class="auth-title">Iniciar sesión</h3>
            <p class="text-muted mb-0" style="font-size: .95rem;">Accede con tu correo y contraseña</p>
          </div>


          <form method="POST" action="{{ route('login.post') }}">
  @csrf



  <div class="mb-3">
    <label for="cedula" class="form-label fw-semibold">Cédula</label>
    <input
      type="text"
      inputmode="numeric"
      pattern="[0-9]*"
      class="form-control"
      id="cedula"
      name="cedula"
      placeholder="Ej: 1234567890"
      required
      autofocus
      autocomplete="username"
      value="{{ old('cedula') }}"
    >
  </div>

  <div class="mb-3">
    <label for="password" class="form-label fw-semibold">Contraseña</label>
    <input
      type="password"
      class="form-control"
      id="password"
      name="password"
      placeholder="••••••••"
      required
      autocomplete="current-password"
    >
  </div>

  <div class="form-check mb-3">
<input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" checked>
    <label class="form-check-label" for="remember">
      Recordarme
    </label>
  </div>

  <button type="submit" class="btn btn-primary w-100 btn-auth">
    Entrar
  </button>

  <div class="d-flex justify-content-between align-items-center mt-3 auth-links" style="font-size: .95rem;">
    <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
    <a href="{{ route('taxistas.create') }}">Registrarse</a>
  </div>
</form>

        </div>
      </div>

      <!-- Pie -->
      <p class="text-center text-muted mt-3 mb-0" style="font-size: .85rem;">
        Los Canarios © {{ date('Y') }}
      </p>
    </div>
  </div>

    @if(session('login_error'))
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: @json(session('login_error')),
      confirmButtonColor: '#d33',
      confirmButtonText: 'Entendido',
    });
  </script>
  @endif

  {{-- ✅ SIEMPRE registrar el service worker --}}
  <script>
    if ("serviceWorker" in navigator) {
      window.addEventListener("load", () => {
        navigator.serviceWorker.register("/sw.js")
          .then(() => console.log("SW OK"))
          .catch(err => console.log("SW ERROR", err));
      });
    }
  </script>
</body>
</html>

