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

  $movilActual = DB::table('movil')
    ->where('mo_conductor', $cedula)
    ->where('mo_estado', '!=', 3)
    ->orderByRaw('CASE WHEN mo_estado = 1 THEN 0 ELSE 1 END')
    ->orderBy('mo_taxi')
    ->select('mo_id', 'mo_taxi', 'mo_estado')
    ->first();

  $movilNumero = $movilActual->mo_taxi ?? 'Sin móvil';
  $movilEstadoNum = (int)($movilActual->mo_estado ?? 0);

  $movilEstado = match ($movilEstadoNum) {
    1 => 'ACTIVO',
    2 => 'INACTIVO',
    default => 'SIN MÓVIL',
  };

  $movilChipUI = match (true) {
    ($estado === 1 && $movilEstadoNum === 1) => [
      'icon' => 'mdi-taxi',
      'bg' => 'rgba(255,255,255,.18)',
      'border' => 'rgba(255,255,255,.22)',
      'text' => '#ffffff',
      'msg' => 'Estás habilitado para recibir servicios.',
    ],
    ($movilEstadoNum === 2) => [
      'icon' => 'mdi-taxi-off',
      'bg' => 'rgba(127,29,29,.92)',
      'border' => 'rgba(255,255,255,.18)',
      'text' => '#ffffff',
      'msg' => 'Tu móvil está INACTIVO. Estás inhabilitado para recibir servicios.',
    ],
    ($estado !== 1) => [
      'icon' => 'mdi-account-off',
      'bg' => 'rgba(51,65,85,.88)',
      'border' => 'rgba(255,255,255,.18)',
      'text' => '#ffffff',
      'msg' => 'No puedes recibir servicios debido a tu estado actual.',
    ],
    default => [
      'icon' => 'mdi-help-circle',
      'bg' => 'rgba(51,65,85,.88)',
      'border' => 'rgba(255,255,255,.18)',
      'text' => '#ffffff',
      'msg' => 'No se encontró un móvil disponible para operar.',
    ],
  };

  $estadoUI = match (true) {
    in_array($estado, [3,5]) => [
      'icon' => 'mdi-alert-decagram',
      'grad' => 'linear-gradient(135deg,#7f1d1d 0%,#ef4444 55%,#f87171 100%)',
      'border' => 'rgba(239,68,68,.35)',
      'msg' => 'Tu cuenta tiene restricciones. Debes acercarte a la oficina.',
    ],
    ($estado === 4) => [
      'icon' => 'mdi-account-cancel',
      'grad' => 'linear-gradient(135deg,#000000 0%,#374151 55%,#111827 100%)',
      'border' => 'rgba(0,0,0,.5)',
      'msg' => 'Tu estado es RETIRADO. No puedes operar.',
    ],
    ($estado === 1 && $movilEstadoNum === 1) => [
      'icon' => 'mdi-check-decagram',
      'grad' => 'linear-gradient(135deg,#0f766e 0%,#22c55e 55%,#86efac 100%)',
      'border' => 'rgba(34,197,94,.35)',
      'msg' => 'Estás habilitado para recibir servicios.',
    ],
    ($movilEstadoNum === 2) => [
      'icon' => 'mdi-taxi-off',
      'grad' => 'linear-gradient(135deg,#1f2937 0%,#6b7280 55%,#9ca3af 100%)',
      'border' => 'rgba(107,114,128,.35)',
      'msg' => 'Tu móvil está inactivo. No recibirás servicios.',
    ],
    ($estado === 2) => [
      'icon' => 'mdi-close-octagon',
      'grad' => 'linear-gradient(135deg,#1f2937 0%,#6b7280 55%,#9ca3af 100%)',
      'border' => 'rgba(107,114,128,.35)',
      'msg' => 'Estás inactivo. No deberías recibir servicios.',
    ],
    default => [
      'icon' => 'mdi-help-circle',
      'grad' => 'linear-gradient(135deg,#334155 0%,#64748b 55%,#94a3b8 100%)',
      'border' => 'rgba(100,116,139,.35)',
      'msg' => 'Estado no definido.',
    ],
  };

  $bloqueado = in_array($estado, [3,4,5], true);

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

<style>
  .estado-link{
    display: block;
    text-decoration: none;
    color: inherit;
  }

  .estado-hero{
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 10px 26px rgba(0,0,0,.10);
    transition: transform .15s ease, box-shadow .15s ease;
  }

  .estado-link:hover .estado-hero{
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0,0,0,.14);
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
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .estado-hero .icon{
    font-size: 54px;
    opacity: .95;
    filter: drop-shadow(0 10px 18px rgba(0,0,0,.25));
  }

  .estado-hero .cta{
    margin-top: 12px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .3px;
    opacity: .95;
  }

  .movil-uber-box{
    position: relative;
    min-width: 150px;
    padding: 14px 20px;
    border-radius: 28px;
    text-align: center;
    color: #fff;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.28);
    box-shadow:
      0 10px 30px rgba(0,0,0,.18),
      inset 0 1px 0 rgba(255,255,255,.18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    overflow: hidden;
  }

  .movil-uber-box::before{
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.14), rgba(255,255,255,.03));
    pointer-events: none;
  }

  .movil-uber-label{
    position: relative;
    z-index: 1;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .3px;
    opacity: .95;
    margin-bottom: 8px;
  }

  .movil-uber-number{
    position: relative;
    z-index: 1;
    font-size: 78px;
    line-height: .95;
    font-weight: 900;
    letter-spacing: -1px;
    text-shadow: 0 6px 20px rgba(0,0,0,.18);
  }

  .movil-uber-active{
    animation: movilPulse 2.2s ease-in-out infinite;
  }

  @keyframes movilPulse{
    0%{
      transform: translateY(0) scale(1);
      box-shadow:
        0 10px 30px rgba(0,0,0,.18),
        inset 0 1px 0 rgba(255,255,255,.18);
    }
    50%{
      transform: translateY(-2px) scale(1.02);
      box-shadow:
        0 16px 38px rgba(0,0,0,.22),
        0 0 0 6px rgba(255,255,255,.06),
        inset 0 1px 0 rgba(255,255,255,.22);
    }
    100%{
      transform: translateY(0) scale(1);
      box-shadow:
        0 10px 30px rgba(0,0,0,.18),
        inset 0 1px 0 rgba(255,255,255,.18);
    }
  }

  .table thead th{ white-space: nowrap; }

  .dash-link{
    text-decoration:none;
    color:inherit;
    display:block;
  }

  .dash-card{
    border: 0;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(15,23,42,.08);
    transition: transform .15s ease, box-shadow .15s ease;
  }

  .dash-card:hover{
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(15,23,42,.12);
  }

  .dash-title{
    font-size: 14px;
  }

  .dash-sub{
    color:#6b7280;
    font-size: 13px;
    line-height: 1.2;
  }

  .dash-icon{
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: #f3f4f6;
    display:flex;
    align-items:center;
    justify-content:center;
    flex: 0 0 56px;
  }

  .mini-pill{
    display:inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    background:#f3f4f6;
    border:1px solid rgba(0,0,0,.06);
    font-weight:700;
  }

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

    .movil-uber-box{
      min-width: 120px;
      padding: 12px 16px;
      border-radius: 24px;
    }

    .movil-uber-label{
      font-size: 13px;
      margin-bottom: 6px;
    }

    .movil-uber-number{
      font-size: 62px;
    }
  }
</style>

<div class="row mb-2">
  <div class="col-12">
    <h3 class="mb-1">MÓDULO CONDUCTOR</h3>
    <p class="text-muted mb-0">Accesos rápidos</p>
  </div>
</div>

<div class="row mb-3">
  <div class="col-12">
    <a href="{{ route('conductor.moviles') }}" class="estado-link">
      <div class="estado-hero">
        <div class="wrap d-flex justify-content-between flex-wrap"
             style="background: {{ $estadoUI['grad'] }}; border-bottom: 1px solid {{ $estadoUI['border'] }};">
          <div class="flex-grow-1" style="min-width:0;">
            <div class="chip">
              <i class="mdi {{ $estadoUI['icon'] }}" style="font-size:18px;"></i>
              ESTADO CONDUCTOR: {{ $estadoTexto }}
            </div>

            <div class="chip mt-2"
                 style="background: {{ $movilChipUI['bg'] }}; border-color: {{ $movilChipUI['border'] }}; color: {{ $movilChipUI['text'] }};">
              <i class="mdi {{ $movilChipUI['icon'] }}" style="font-size:18px;"></i>
              ESTADO MÓVIL: {{ $movilEstado }}
            </div>

            <p class="sub mb-0 mt-3">
              Conductor: <strong>{{ $conductor->conduc_nombres }}</strong>
            </p>

            <p class="sub mb-0">
              Cédula: <strong>{{ $cedula }}</strong>
            </p>

            <p class="sub mb-0 mt-2">
              Móvil: <strong>{{ $movilNumero }}</strong>
            </p>

            <p class="sub mb-0">
              Estado móvil: <strong>{{ $movilEstado }}</strong>
            </p>

            <div class="hint">
              {{ $estadoUI['msg'] }}

              <div class="mt-2" style="font-weight:800;">
                {{ $movilChipUI['msg'] }}
              </div>

              @if($bloqueado)
                <div class="mt-2" style="font-weight:800;">
                  ⛔ Estado bloqueado: no podrás operar ni recibir servicios.
                </div>
              @endif
            </div>

            <div class="cta">
              Toca para administrar tu móvil
            </div>
          </div>

          <div class="right">
            @if($movilEstadoNum === 1)
              <div class="movil-uber-box movil-uber-active">
                <div class="movil-uber-label">Móvil</div>
                <div class="movil-uber-number">{{ $movilNumero }}</div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </a>
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

  <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
    <div class="card dash-card">
      <div class="card-body">
        <p class="mb-2 text-muted dash-title">Notificaciones push</p>

        <div class="d-flex flex-column gap-2">
          <button type="button" class="btn btn-outline-primary mb-2" onclick="verPush()">
            Ver estado push
          </button>

          <button type="button" class="btn btn-primary" onclick="testPush()">
            Probar activar push
          </button>
        </div>

        <div id="pushTestResult" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

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

  async function verPush() {
    try {
      if (!('serviceWorker' in navigator)) {
        alert('Este navegador no soporta Service Worker');
        return;
      }

      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();

      alert(
        "Permiso: " + Notification.permission +
        "\nSuscripción: " + (sub ? "SI" : "NO") +
        "\nEndpoint: " + (sub ? sub.endpoint : "ninguno")
      );
    } catch (e) {
      console.error(e);
      alert('Error revisando estado push: ' + e.message);
    }
  }

  async function enablePushNotifications() {
    if (!('Notification' in window)) {
      throw new Error('Este navegador no soporta notificaciones');
    }

    if (!('serviceWorker' in navigator)) {
      throw new Error('Este navegador no soporta Service Worker');
    }

    if (!('PushManager' in window)) {
      throw new Error('Este navegador no soporta Push API');
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
      throw new Error('Permiso no concedido: ' + permission);
    }

    const reg = await navigator.serviceWorker.ready;
    let sub = await reg.pushManager.getSubscription();

    if (!sub) {
      const publicKey = @json(config('services.webpush.public_key'));

      if (!publicKey) {
        throw new Error('No existe la clave pública WEBPUSH_PUBLIC_KEY');
      }

      sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey)
      });
    }

    const jsonSub = sub.toJSON();

    const response = await fetch(@json(route('conductor.push.subscribe')), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': @json(csrf_token()),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        endpoint: jsonSub.endpoint,
        keys: {
          p256dh: jsonSub.keys.p256dh,
          auth: jsonSub.keys.auth
        }
      })
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || 'No se pudo guardar la suscripción en el servidor');
    }

    return { sub, result };
  }

  async function testPush() {
    const box = document.getElementById('pushTestResult');

    try {
      if (box) {
        box.innerHTML = '<div class="alert alert-info mb-0">Iniciando prueba…</div>';
      }

      const { sub } = await enablePushNotifications();

      console.log('Suscripción creada/encontrada:', sub);

      if (box) {
        box.innerHTML = `
          <div class="alert alert-success mb-2">
            Suscripción OK y guardada en servidor
          </div>
          <div class="small text-muted" style="word-break:break-all;">
            <strong>Permiso:</strong> ${Notification.permission}<br>
            <strong>Endpoint:</strong> ${sub.endpoint}
          </div>
        `;
      }

      alert('Suscripción creada y guardada correctamente');
    } catch (e) {
      console.error(e);

      if (box) {
        box.innerHTML = `
          <div class="alert alert-danger mb-2">
            Error al activar push
          </div>
          <div class="small text-muted">
            ${e.message}
          </div>
        `;
      }

      alert('Error: ' + e.message);
    }
  }

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
  }

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
          await enablePushNotifications();

          if (msg) {
            msg.innerHTML = '<div class="alert alert-success mb-0">¡Listo! Notificaciones activadas.</div>';
          }

          setTimeout(() => {
            if (window.$) $('#modalPush').modal('hide');
          }, 700);

        } catch (e) {
          console.error(e);
          if (msg) {
            msg.innerHTML = `<div class="alert alert-danger mb-0">${e.message}</div>`;
          }
        }
      }, { once: true });
    });
  })();
</script>
@endsection