{{-- resources/views/modulos/conductor.blade.php --}}
@extends('layouts.app_conductor')

@section('title', 'Módulo Conductor')

@section('content')
@php
  use Illuminate\Support\Facades\DB;

  $cedula = auth()->user()->cedula;

  $conductor = DB::table('conductores')
    ->where('conduc_cc', $cedula)
    ->select('conduc_estado','conduc_nombres','conduc_cc')
    ->first();

  $estado = (int)($conductor->conduc_estado ?? 2);

  $estadoTexto = match ($estado) {
    1 => 'ACTIVO',
    2 => 'INACTIVO',
    3 => 'SANCIONADO',
    4 => 'RETIRADO',
    5 => 'EVADIDO',
    default => 'DESCONOCIDO',
  };

  // UI del banner
  $estadoUI = match ($estado) {
    1 => [
      'icon' => 'mdi-check-decagram',
      'grad' => 'linear-gradient(135deg,#0f766e 0%,#22c55e 55%,#86efac 100%)',
      'border' => 'rgba(34,197,94,.35)',
      'msg' => 'Estás habilitado para recibir servicios.',
    ],
    2 => [
      'icon' => 'mdi-close-octagon',
      'grad' => 'linear-gradient(135deg,#7f1d1d 0%,#ef4444 55%,#fb7185 100%)',
      'border' => 'rgba(239,68,68,.35)',
      'msg' => 'Estás inactivo. No deberían asignarte servicios.',
    ],
    3 => [
      'icon' => 'mdi-alert-decagram',
      'grad' => 'linear-gradient(135deg,#7c2d12 0%,#f97316 55%,#fde68a 100%)',
      'border' => 'rgba(249,115,22,.35)',
      'msg' => 'Tu cuenta está SANCIONADA. Debes acercarte a la oficina principal.',
    ],
    4 => [
      'icon' => 'mdi-account-cancel',
      'grad' => 'linear-gradient(135deg,#111827 0%,#6b7280 55%,#374151 100%)',
      'border' => 'rgba(107,114,128,.35)',
      'msg' => 'Tu estado figura como RETIRADO. Debes acercarte a la oficina principal.',
    ],
    5 => [
      'icon' => 'mdi-shield-alert',
      'grad' => 'linear-gradient(135deg,#4c1d95 0%,#7c3aed 55%,#c4b5fd 100%)',
      'border' => 'rgba(124,58,237,.35)',
      'msg' => 'Tu cuenta está EVADIDA. Debes acercarte a la oficina principal.',
    ],
    default => [
      'icon' => 'mdi-help-circle',
      'grad' => 'linear-gradient(135deg,#334155 0%,#64748b 55%,#94a3b8 100%)',
      'border' => 'rgba(100,116,139,.35)',
      'msg' => 'No se pudo determinar tu estado. Comunícate con la oficina.',
    ],
  };

  $bloqueado = in_array($estado, [3,4,5], true);

  // Hoy en Bogotá (para comparar con dis_fecha)
  $hoy = now()->setTimezone('America/Bogota')->format('Y-m-d');

  // Último servicio
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

  // Facturación 5 días
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

<style>
  /* ===== Banner estado ===== */
  .estado-hero{
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 10px 26px rgba(0,0,0,.10);
  }

  .estado-hero .wrap{
    padding: 18px 18px;
    color:#fff;
    position: relative;
    gap: 14px;
  }

  .estado-hero .big{
    font-weight: 900;
    letter-spacing: .4px;
    font-size: 22px;
    line-height: 1.15;
    margin: 10px 0 0 0;
    word-break: break-word;
  }

  .estado-hero .sub{
    opacity:.92;
    margin: 6px 0 0 0;
    font-size: 14px;
    line-height: 1.25;
  }

  .estado-hero .chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.22);
    font-weight: 900;
    letter-spacing: .3px;
    font-size: 12px;
    line-height: 1;
    max-width: 100%;
    white-space: nowrap;
  }

  .estado-hero .hint{
    margin-top: 12px;
    background: rgba(0,0,0,.16);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 14px;
    padding: 10px 12px;
    font-size: 13px;
    line-height: 1.25;
  }

  .estado-hero .right{
    text-align:right;
    min-width: 160px;
    flex: 0 0 auto;
  }

  .estado-hero .icon{
    font-size: 54px;
    opacity: .95;
    filter: drop-shadow(0 10px 18px rgba(0,0,0,.25));
  }

  /* ✅ Ajuste para móvil: evita que se “monten” los textos */
  @media (max-width: 576px){
    .estado-hero .wrap{
      padding: 16px 14px;
      flex-direction: column;
      align-items: flex-start !important;
    }

    .estado-hero .right{
      width: 100%;
      text-align: center;
      min-width: 0;
      margin-top: 6px;
      opacity: .9;
    }

    .estado-hero .icon{
      font-size: 44px;
    }

    .estado-hero .chip{
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .estado-hero .big{
      font-size: 20px;
      margin-top: 10px;
    }

    .estado-hero .sub{
      font-size: 13px;
    }
  }
</style>

<div class="row mb-2">
  <div class="col-12">
    <h3 class="mb-1">MÓDULO CONDUCTOR</h3>
    <p class="text-muted mb-0">Accesos rápidos</p>
  </div>
</div>

{{-- ===== Banner llamativo (lo primero que ven) ===== --}}
<div class="row mb-3">
  <div class="col-12">
   <div class="estado-hero">
  <div class="wrap d-flex justify-content-between flex-wrap"
       style="background: {{ $estadoUI['grad'] }}; border-bottom: 1px solid {{ $estadoUI['border'] }};">
    <div class="flex-grow-1" style="min-width:0;">
      <div class="chip">
        <i class="mdi {{ $estadoUI['icon'] }}" style="font-size:18px;"></i>
        ESTADO: {{ $estadoTexto }}
      </div>


       <p class="sub mb-0">
        Conductor: <strong>{{ $conductor->conduc_nombres }}</strong>
      </p>

      <p class="sub mb-0">
        Cédula: <strong>{{ $cedula }}</strong>
      </p>

      <div class="hint">
        {{ $estadoUI['msg'] }}
        @if($bloqueado)
          <div class="mt-1" style="font-weight:800;">
            ⛔ Estado bloqueado: no podrás operar ni recibir servicios.
          </div>
        @endif
      </div>
    </div>

    <div class="right">
      <i class="mdi {{ $estadoUI['icon'] }} icon"></i>
    </div>
  </div>
</div>
  </div>
</div>

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

    if (localStorage.getItem(KEY) === '1') return;
    if (!('Notification' in window)) return;

    if (Notification.permission === 'granted' || Notification.permission === 'denied') {
      localStorage.setItem(KEY, '1');
      return;
    }

    document.addEventListener('DOMContentLoaded', function () {
      localStorage.setItem(KEY, '1');

      if (window.$ && $('#modalPush').length) {
        $('#modalPush').modal({ backdrop: 'static', keyboard: true });
        $('#modalPush').modal('show');
      }

      const btnLater = document.getElementById('btnPushLater');
      btnLater?.addEventListener('click', () => {});

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