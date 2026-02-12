<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    

    <title>@yield('title', 'Los Canarios')</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />

    
</head>
<body>

    <div class="container-scroller">
        <!-- Sidebar -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <div class="text-center sidebar-brand-wrapper d-flex align-items-center">
                {{-- ✅ CAMBIO: antes route('dashboard') --}}
                <a class="sidebar-brand brand-logo" href="{{ url('/modulo-conductor') }}">
                    <img src="{{ asset('assets/images/logo_transparente.png') }}" alt="logo" />
                </a>
                {{-- ✅ CAMBIO: antes route('dashboard') --}}
                <a class="sidebar-brand brand-logo-mini pl-4 pt-3" href="{{ url('/modulo-conductor') }}">
                    <img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" />
                </a>
            </div>
            
            <ul class="nav">
                
                
                <li class="nav-item">
                    
                    <a class="nav-link" href="{{ url('/modulo-conductor') }}">

                        <i class="mdi mdi-home menu-icon"></i>
                        <span class="menu-title">Menu Principal</span>
                    </a>
                </li>
                    <button type="button" class="btn btn-primary" onclick="enablePushNotifications()">
  Activar notificaciones
</button>
                


                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/conductor/servicios-asignados') }}">
                        <i class="mdi mdi-clipboard-list-outline menu-icon"></i>
                        <span class="menu-title">Listado de servicios</span>
                    </a>
                </li>

                <li class="nav-item">
    <a class="nav-link" href="{{ route('conductor.facturacion.index') }}">
        <i class="mdi mdi-cash menu-icon"></i>
        <span class="menu-title">Facturación</span>
    </a>
</li>

               

                <li class="nav-item">
                    <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                        <i class="mdi mdi-taxi menu-icon"></i>
                        <span class="menu-title">Taxis</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse" id="ui-basic">
                        <ul class="nav flex-column sub-menu">
                            
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/conductor/taxi') }}">Editar</a>
                            </li>
                           
                        </ul>
                    </div>
                </li>

                    <li class="nav-item">
                    <a class="nav-link" data-toggle="collapse" href="#ui-basic1" aria-expanded="false" aria-controls="ui-basic1">
                        <i class="mdi mdi-taxi menu-icon"></i>
                        <span class="menu-title">Conductores</span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse" id="ui-basic1">
                        <ul class="nav flex-column sub-menu">
                            
                            <li class="nav-item">
  <a class="nav-link" href="{{ route('conductor.moviles') }}">
    <i class="mdi mdi-taxi menu-icon"></i>
    <span class="menu-title">Mis Móviles</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="{{ route('conductor.perfil.edit') }}">
    <i class="mdi mdi-account-circle menu-icon"></i>
    <span class="menu-title">Mi Perfil</span>
  </a>
</li>
                                                   
                        </ul>
                    </div>
                </li>
                
                                           
                <li class="nav-item sidebar-actions">
                    <div class="nav-link">
                        <div class="mt-4">
                            <div class="border-none">
                                
                            </div>
                            <ul class="mt-4 pl-0">
                                <li>
                                    <a class="dropdown-item"
   href="{{ route('logout') }}"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
    <i class="mdi mdi-logout mr-2 text-primary"></i> Cerrar Sesion
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content Wrapper -->
        <div class="container-fluid page-body-wrapper">
            <!-- Theme Settings Panel -->
            <div id="theme-settings" class="settings-panel">
                <i class="settings-close mdi mdi-close"></i>
                <p class="settings-heading">SIDEBAR SKINS</p>
                <div class="sidebar-bg-options selected" id="sidebar-default-theme">
                    <div class="img-ss rounded-circle bg-light border mr-3"></div> Default
                </div>
                <div class="sidebar-bg-options" id="sidebar-dark-theme">
                    <div class="img-ss rounded-circle bg-dark border mr-3"></div> Dark
                </div>
                <p class="settings-heading mt-2">HEADER SKINS</p>
                <div class="color-tiles mx-0 px-4">
                    <div class="tiles light"></div>
                    <div class="tiles dark"></div>
                </div>
            </div>
            
            <!-- Top Navbar -->
            <nav class="navbar col-lg-12 col-12 p-lg-0 fixed-top d-flex flex-row">
                <div class="navbar-menu-wrapper d-flex align-items-stretch justify-content-between">
                    {{-- ✅ CAMBIO: antes route('dashboard') --}}
                    <a class="navbar-brand brand-logo-mini align-self-center d-lg-none" href="{{ url('/modulo-conductor') }}">
                        <img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" />
                    </a>
                    <button class="navbar-toggler navbar-toggler align-self-center mr-2" type="button" data-toggle="minimize">
                        <i class="mdi mdi-menu"></i>
                    </button>
                    
                    @php
    $unreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $lastUnread = auth()->check()
        ? auth()->user()->unreadNotifications()->latest()->take(5)->get()
        : collect();
@endphp
                    <ul class="navbar-nav">
                      <li class="nav-item dropdown">
    <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown">
        <i class="mdi mdi-bell-outline"></i>
        @if($unreadCount > 0)
            <span class="count count-varient1">{{ $unreadCount }}</span>
        @endif
    </a>

    <div class="dropdown-menu navbar-dropdown navbar-dropdown-large preview-list" aria-labelledby="notificationDropdown">
        <h6 class="p-3 mb-0">Notificaciones</h6>

        @if($unreadCount === 0)
            <div class="dropdown-divider"></div>
            <p class="p-3 mb-0 text-muted">Sin notificaciones</p>
        @else
            @foreach($lastUnread as $n)
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item" href="{{ route('conductor.notificaciones.leer', $n->id) }}">
                    <div class="preview-item-content">
                        <p class="mb-0">
                            {{ $n->data['titulo'] ?? 'Notificación' }}
                            <span class="text-small text-muted d-block">
                                {{ $n->data['mensaje'] ?? '' }}
                            </span>
                        </p>
                    </div>
                </a>
            @endforeach

            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-center" href="{{ route('conductor.notificaciones') }}">
                Ver todas
            </a>
        @endif
    </div>
</li>
                        
                    </ul>
                    
                    <ul class="navbar-nav navbar-nav-right ml-lg-auto">
                        <li class="nav-item dropdown d-none d-xl-flex border-0">
                            <a class="nav-link dropdown-toggle" id="languageDropdown" href="#" data-toggle="dropdown">
                                <i class="mdi mdi-earth"></i> Español
                            </a>
                            <div class="dropdown-menu navbar-dropdown" aria-labelledby="languageDropdown">
                                <a class="dropdown-item" href="#">Español</a>
                               
                            </div>
                        </li>
                        
                        <li class="nav-item nav-profile dropdown border-0">
                            <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown">
                                <img class="nav-profile-img mr-2" alt="" src="{{ asset('assets/images/faces/face5.jpg') }}" />
                                @auth
    <span class="profile-name">{{ \Illuminate\Support\Facades\Auth::user()->name }}</span>
@endauth
                            </a>
                            <div class="dropdown-menu navbar-dropdown w-100" aria-labelledby="profileDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="mdi mdi-cached mr-2 text-success"></i> Cambiar Contraseña
                                </a>
                                <a class="dropdown-item"
   href="{{ route('logout') }}"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
    <i class="mdi mdi-logout mr-2 text-primary"></i> Cerrar Sesion
</a>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

                            </div>
                        </li>
                    </ul>
                    
                    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                        <span class="mdi mdi-menu"></span>
                    </button>
                </div>
            </nav>
            
            <!-- Main Panel -->
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('content')
                </div>
                
                <!-- Footer -->
                <footer class="footer">
                    <div class="d-sm-flex justify-content-center justify-content-sm-between">
                        <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © Los Canarios 2025</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('assets/vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('assets/js/misc.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    
    <script>
async function enablePushNotifications() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    alert('Tu navegador no soporta notificaciones push.');
    return;
  }

  // Registra el SW (no es PWA, solo SW)
  const reg = await navigator.serviceWorker.register('/sw.js');

  // Pide permiso al usuario
  const perm = await Notification.requestPermission();
  if (perm !== 'granted') return;

  const publicKey = "{{ config('services.webpush.public_key') }}";
  if (!publicKey) {
    console.error('Falta WEBPUSH_PUBLIC_KEY en config/services.php');
    return;
  }

  const sub = await reg.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(publicKey)
  });

  await fetch("{{ route('conductor.push.subscribe') }}", {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(sub)
  });

  console.log('Suscripción push guardada ✅');
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}
</script>

    @yield('scripts')
</body>
</html>
