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

  $taxisConductor = DB::table('movil')
    ->where('mo_conductor', $cedula)
    ->pluck('mo_id');

  $ultimoServicio = null;

  if ($taxisConductor->count() > 0) {
    $ultimoServicio = DB::table('disponibles')
      ->whereIn('dis_conmo', $taxisConductor)
      ->orderByDesc('dis_fecha')
      ->orderByDesc('dis_hora')
      ->select('dis_dire', 'dis_usuario')
      ->first();
  }

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

<div class="row">

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

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <a class="dash-link" href="{{ route('conductor.moviles') }}">
      <div class="card dash-card">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <p class="mb-1 text-muted dash-title">Mis móviles</p>
            <h5 class="mb-0">Activar / desactivar</h5>
            <p class="dash-sub mt-1">Activa o desactiva el móvil</p>
          </div>
          <div class="dash-icon">
            <i class="mdi mdi-toggle-switch text-success"></i>
          </div>
        </div>
      </div>
    </a>
  </div>

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

  {{-- ... el resto de tus tarjetas igual ... --}}

</div>

{{-- ✅ MODAL Push (Bootstrap 4) --}}
<div class="modal fade" id="modalPush" tabindex="-1" role="dialog" aria-labelledby="modalPushLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:520px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPushLabel">Activar notificaciones</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <p class="mb-2">
          Activa las notificaciones para avisarte cuando tengas <strong>servicios asignados</strong> o alertas importantes.
        </p>
        <small class="text-muted">
          Puedes desactivarlas cuando quieras desde la configuración del navegador.
        </small>

        <div id="pushModalMsg" class="mt-3"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" id="btnPushLater">
          Ahora no
        </button>
        <button type="button" class="btn btn-primary" id="btnPushEnable">
          Activar
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  // ✅ tu script existente para Servicios Hoy
  (function(){
    const hoy = "{{ $hoy }}";
    const el = document.getElementById('serviciosHoy');
    const url = "{{ route('conductor.servicios_asignados.listar') }}";

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(json => {
        const rows = json.data || [];
        const countHoy = rows.filter(r => String(r.fecha || '') === hoy).length;
        el.textContent = String(countHoy);
      })
      .catch(() => {
        el.textContent = '0';
      });
  })();
</script>

<script>
  // ✅ Modal push - se muestra solo una vez y solo si permission == "default"
  (function pushModalOnce(){
    const KEY = 'push_modal_prompted_once';

    // ya lo mostramos antes
    if (localStorage.getItem(KEY) === '1') return;

    // si no soporta
    if (!('Notification' in window)) return;

    // si ya está granted/denied no lo mostramos
    if (Notification.permission === 'granted' || Notification.permission === 'denied') {
      localStorage.setItem(KEY, '1');
      return;
    }

    document.addEventListener('DOMContentLoaded', function () {
      // marcar “ya mostrado” para no insistir si recargan
      localStorage.setItem(KEY, '1');

      // abre modal (Bootstrap 4 => jQuery)
      if (window.$ && $('#modalPush').length) {
        $('#modalPush').modal({ backdrop: 'static', keyboard: true });
        $('#modalPush').modal('show');
      }

      // ahora no
      const btnLater = document.getElementById('btnPushLater');
      btnLater?.addEventListener('click', () => {
        // aquí podrías guardar timestamp si quieres reintentar luego
      });

      // activar
      const btnEnable = document.getElementById('btnPushEnable');
      btnEnable?.addEventListener('click', async () => {
        const msg = document.getElementById('pushModalMsg');
        if (msg) msg.innerHTML = '<div class="alert alert-info mb-0">Activando…</div>';

        try {
          if (typeof enablePushNotifications !== 'function') {
            throw new Error('enablePushNotifications() no está definida.');
          }

          await enablePushNotifications();

          if (msg) msg.innerHTML = '<div class="alert alert-success mb-0">¡Listo! Notificaciones activadas.</div>';

          // cerrar
          setTimeout(() => {
            if (window.$) $('#modalPush').modal('hide');
          }, 700);

        } catch (e) {
          console.error(e);
          if (msg) msg.innerHTML = '<div class="alert alert-danger mb-0">No se pudo activar. Intenta nuevamente.</div>';
        }
      }, { once: true });
    });
  })();
</script>
@endsection
