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
            <a class="sidebar-brand brand-logo" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/images/logo_transparente.png') }}" alt="logo" />
            </a>
            <a class="sidebar-brand brand-logo-mini pl-4 pt-3" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" />
            </a>
        </div>

        <ul class="nav">

            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="mdi mdi-home menu-icon"></i>
                    <span class="menu-title">Menu Principal</span>
                </a>
            </li>

            {{-- ✅ SERVICIOS --}}
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse"
                   href="#menu-servicios" aria-expanded="false" aria-controls="menu-servicios"
                   data-menu="servicios">
                    <i class="mdi mdi-hail menu-icon"></i>
                    <span class="menu-title">Servicios</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="menu-servicios" data-parent="#sidebar">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/servicios/asignar') }}">
                                <i class="mdi mdi-plus-box-outline mr-1"></i> Asignar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/servicios/listado') }}">
                                <i class="mdi mdi-clipboard-list-outline mr-1"></i> Listado de servicios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/servicios/consulta') }}">
                                <i class="mdi mdi-cloud-search-outline mr-1"></i> Consulta por Token
                            </a>
                        </li>
                        <li class="nav-item">
  <a class="nav-link" href="{{ route('operadora.facturacion') }}">
    <i class="mdi mdi-cash-multiple menu-icon"></i>
    <span class="menu-title">Facturación</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="{{ route('operadora.recaudado') }}">
    <i class="mdi mdi-cash-register menu-icon"></i>
    <span class="menu-title">Recaudado</span>
  </a>
</li>

<li class="nav-item">
  <a class="nav-link" href="{{ route('operadora.facturas_pendientes') }}">
    <i class="mdi mdi-file-document-outline menu-icon"></i>
    <span class="menu-title">Facturas pendientes</span>
  </a>
</li>


<li class="nav-item">
  <a class="nav-link" href="{{ route('operadora.recaudado.historial') }}">
    <i class="mdi mdi-history menu-icon"></i>
    <span class="menu-title">Historial Recaudos</span>
  </a>
</li>
                    </ul>
                </div>
            </li>

            {{-- TAXIS --}}
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse"
                   href="#menu-taxis" aria-expanded="false" aria-controls="menu-taxis"
                   data-menu="taxis">
                    <i class="mdi mdi-taxi menu-icon"></i>
                    <span class="menu-title">Taxis</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="menu-taxis" data-parent="#sidebar">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item"><a class="nav-link" href="{{ url('/taxis/crear') }}">Agregar</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/taxis/editar-fechas') }}">Editar</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/taxis/panel') }}">Estado</a></li>
                    </ul>
                </div>
            </li>

            {{-- ✅ CONDUCTORES --}}
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse"
                   href="#menu-conductores" aria-expanded="false" aria-controls="menu-conductores"
                   data-menu="conductores">
                    <i class="mdi mdi-account-group menu-icon"></i>
                    <span class="menu-title">Conductores</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="menu-conductores" data-parent="#sidebar">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item"><a class="nav-link" href="{{ url('/conductores') }}">Estado</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/register') }}">Agregar</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/conductores/editar-licencia') }}">Editar</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/conductores/asignar') }}">Asignar Movil</a></li>
                    </ul>
                </div>
            </li>

            {{-- ✅ SANCIONES --}}
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse"
                   href="#menu-sanciones" aria-expanded="false" aria-controls="menu-sanciones"
                   data-menu="sanciones">
                    <i class="mdi mdi-alert-octagon menu-icon"></i>
                    <span class="menu-title">Sanciones</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse" id="menu-sanciones" data-parent="#sidebar">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/operadora/sancionar') }}">
                                <i class="mdi mdi-gavel mr-1"></i> Sancionar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/operadora/sanciones/levantar') }}">
                                <i class="mdi mdi-check-decagram mr-1"></i> Levantar
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- LOGOUT --}}
            <li class="nav-item sidebar-actions">
                <div class="nav-link">
                    <div class="mt-4">
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
                <a class="navbar-brand brand-logo-mini align-self-center d-lg-none" href="{{ route('dashboard') }}">
                    <img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" />
                </a>
                <button class="navbar-toggler navbar-toggler align-self-center mr-2" type="button" data-toggle="minimize">
                    <i class="mdi mdi-menu"></i>
                </button>

                <ul class="navbar-nav navbar-nav-right ml-lg-auto">
                    <li class="nav-item nav-profile dropdown border-0">
                        <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown">
                            <img class="nav-profile-img mr-2" alt="" src="{{ asset('assets/images/faces/face5.jpg') }}" />
                            @auth
                                <span class="profile-name">{{ \Illuminate\Support\Facades\Auth::user()->name }}</span>
                            @endauth
                        </a>
                        <div class="dropdown-menu navbar-dropdown w-100" aria-labelledby="profileDropdown">
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
                    <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">
                        Copyright © Los Canarios 2025
                    </span>
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

{{-- ✅ SOLO UN MENÚ ABIERTO SEGÚN URL --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;

    const map = {
        servicios:    ['/servicios'],
        taxis:        ['/taxis'],
        conductores:  ['/conductores'],
        sanciones:    ['/operadora/sanciones', '/operadora/sancionar']
    };

    // Cerrar todos los collapse
    document.querySelectorAll('#sidebar .collapse').forEach(el => {
        el.classList.remove('show');
    });

    // Abrir solo el correspondiente
    document.querySelectorAll('#sidebar .nav-link[data-menu]').forEach(link => {
        const key = link.getAttribute('data-menu');
        const prefixes = map[key] || [];

        const match = prefixes.some(p => path.startsWith(p));
        if (!match) return;

        const target = link.getAttribute('href'); // ej: #menu-conductores
        const panel = document.querySelector(target);
        if (panel) panel.classList.add('show');
    });
});
</script>

@yield('scripts')
</body>
</html>
