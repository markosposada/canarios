{{-- resources/views/modulos/conductor.blade.php --}}
@extends('layouts.app_conductor')

@section('title', 'Módulo Conductor')

@section('content')
@php
  use Illuminate\Support\Facades\DB;

  $cedula = auth()->user()->cedula;

  $conductor = DB::table('conductores')
    ->where('conduc_cc', $cedula)
    ->select('conduc_estado','conduc_nombres')
    ->first();

  $estado = (int)($conductor->conduc_estado ?? 2);

  $estadoTexto = match ($estado) {
    1 => 'ACTIVO',
    2 => 'INACTIVO',
    3 => 'SANCIONADO',
    4 => 'RETIRADO',
    default => 'DESCONOCIDO',
  };

  $estadoClase = match ($estado) {
    1 => 'text-success',
    2 => 'text-danger',
    3 => 'text-warning',
    4 => 'text-dark',
    default => 'text-secondary',
  };

  // Hoy en Bogotá (para comparar con dis_fecha)
  $hoy = now()->setTimezone('America/Bogota')->format('Y-m-d');

  /**
   * ✅ Último servicio asignado (Dirección + Usuario) DESDE disponibles
   * dis_conmo = número del móvil (taxi)
   * movil.mo_id = número del móvil del conductor
   */
  $taxisConductor = DB::table('movil')
    ->where('mo_conductor', $cedula)
    ->pluck('mo_id'); // colección de taxis (números)

  $ultimoServicio = null;

  if ($taxisConductor->count() > 0) {
    $ultimoServicio = DB::table('disponibles')
      ->whereIn('dis_conmo', $taxisConductor)
      ->orderByDesc('dis_fecha')
      ->orderByDesc('dis_hora')
      ->select('dis_dire', 'dis_usuario')
      ->first();
  }

    // ✅ Facturación últimos 5 días (incluye hoy)
  $desdeFact = now()->setTimezone('America/Bogota')->subDays(5)->format('Y-m-d');

  $fact = DB::table('facturacion_operadora as fo')
    ->where('fo.fo_conductor', $cedula)
    ->whereDate('fo.fo_fecha', '>=', $desdeFact)
    ->selectRaw('
        COUNT(*) as registros,
        COALESCE(SUM(fo.fo_total),0) as facturado,
        COALESCE(SUM(CASE WHEN fo.fo_pagado = 1 THEN fo.fo_total ELSE 0 END),0) as pagado,
        COALESCE(SUM(CASE WHEN fo.fo_pagado = 0 THEN fo.fo_total ELSE 0 END),0) as debe
    ')
    ->first();

  $factDebe = (int)($fact->debe ?? 0);
  $factPagado = (int)($fact->pagado ?? 0);
  $factFacturado = (int)($fact->facturado ?? 0);

@endphp

<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">MÓDULO CONDUCTOR</h3>
    <p class="text-muted mb-0">Accesos rápidos</p>
  </div>
</div>

<style>
  .dash-card {
    transition: transform .12s ease, box-shadow .12s ease;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,.06);
  }
  .dash-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.10); }
  .dash-card .card-body { padding: 18px 18px; }
  .dash-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(0,0,0,.04);
  }
  .dash-title { font-size: .95rem; letter-spacing: .2px; }
  .dash-sub { font-size: .85rem; color:#6c757d; margin:0; }
  .dash-link { text-decoration:none !important; color: inherit !important; display:block; }

  .mini-pill {
    display:inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    font-size: 12px;
    font-weight: 800;
  }
</style>

{{-- ===================== TARJETAS ARRIBA ===================== --}}
<div class="row">

  {{-- Último servicio asignado --}}
  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
<a class="dash-link" href="{{ route('conductor.ultimo_servicio') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Último servicio</p>
            <h6 class="mb-0">
              {{ $ultimoServicio->dis_dire ?? 'Sin asignaciones' }}
            </h6>
            <p class="dash-sub mt-1">
              Usuario: <span class="mini-pill">{{ $ultimoServicio->dis_usuario ?? '—' }}</span>
            </p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-map-marker mdi-28px text-info"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  {{-- Estado real del conductor --}}
  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.estado') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Mi estado</p>
            <h5 class="mb-0 {{ $estadoClase }}">{{ $estadoTexto }}</h5>
            <p class="dash-sub mt-1">Toca para ver / cambiar</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-account-switch mdi-28px {{ $estadoClase }}"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  {{-- Servicios asignados hoy --}}
  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.servicios_asignados') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Servicios hoy</p>
            <h5 class="mb-0" id="serviciosHoy">0</h5>
            <p class="dash-sub mt-1">
              <span class="mini-pill">Fecha: {{ $hoy }}</span>
            </p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-calendar-check mdi-28px text-primary"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  {{-- Facturación últimos 5 días --}}
<div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
  <a class="dash-link" href="{{ route('conductor.facturacion.index') }}">
    <div class="card dash-card">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <p class="mb-1 text-muted dash-title">Facturación (5 días)</p>
          <h5 class="mb-0 {{ $factDebe > 0 ? 'text-danger' : 'text-success' }}">
            {{ '$' . number_format($factDebe, 0, ',', '.') }}
          </h5>
          <p class="dash-sub mt-1">
            Debe · Pagado: <span class="mini-pill">{{ '$' . number_format($factPagado, 0, ',', '.') }}</span>
          </p>
        </div>
        <div class="dash-icon">
          <i class="mdi mdi-cash mdi-28px {{ $factDebe > 0 ? 'text-danger' : 'text-success' }}"></i>
        </div>
      </div>
    </div>
  </a>
</div>



  {{-- Puedes dejar los otros accesos como estaban --}}
  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.taxi.edit') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Mi Taxi</p>
            <h5 class="mb-0">Editar datos</h5>
            <p class="dash-sub mt-1">Placa, SOAT, Tecno</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-car mdi-28px text-info"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.moviles') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Mis móviles</p>
            <h5 class="mb-0">Activar / desactivar</h5>
            <p class="dash-sub mt-1">Control del estado</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-cellphone-link mdi-28px text-warning"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.notificaciones') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Notificaciones</p>
            <h5 class="mb-0">Centro</h5>
            <p class="dash-sub mt-1">Alertas y avisos</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-bell-outline mdi-28px text-danger"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.perfil.edit') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Perfil</p>
            <h5 class="mb-0">Mis datos</h5>
            <p class="dash-sub mt-1">Actualiza tu info</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-account-edit mdi-28px text-primary"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Salir</p>
            <h5 class="mb-0">Cerrar sesión</h5>
            <p class="dash-sub mt-1">Terminar la sesión</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-logout mdi-28px text-dark"></i>
          </div>
        </div>
      </div>
    </a>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
      @csrf
    </form>
  </div>

</div>
@endsection

@section('scripts')
<script>
  (function(){
    const hoy = "{{ $hoy }}";
    const el = document.getElementById('serviciosHoy');

    // Usa el endpoint nuevo (ya trae disponibles filtrado + últimos 3 días)
    const url = "{{ route('conductor.servicios_asignados.listar') }}";

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(json => {
        const rows = json.data || [];
        // ✅ ahora viene como "fecha" (no fac_fecha)
        const countHoy = rows.filter(r => String(r.fecha || '') === hoy).length;
        el.textContent = String(countHoy);
      })
      .catch(() => {
        el.textContent = '0';
      });
  })();
</script>
@endsection
