@extends('layouts.app_conductor')

@section('title', 'Mis Móviles')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="border-radius:18px; overflow:hidden;">

      {{-- HEADER --}}
      <div class="p-4 text-white"
           style="background: linear-gradient(135deg, #0f172a 0%, #2563eb 45%, #22c55e 100%);">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h4 class="mb-1">🚖 Mis Móviles</h4>
            <div class="opacity-75">
              Solo un móvil puede estar activo a la vez.
            </div>
          </div>
          <div class="mt-3 mt-md-0">
            <span class="badge badge-pill badge-light" style="padding:10px 14px;">
              Cédula: <strong>{{ auth()->user()->cedula }}</strong>
            </span>
          </div>
        </div>
      </div>

      {{-- BODY --}}
      <div class="card-body p-4">

        @if($moviles->count() === 0)
          <div class="alert alert-warning mb-0" style="border-radius:14px;">
            No tienes móviles asignados.
          </div>
        @else
          <div class="row">

            @foreach($moviles as $m)
              @php
                $estado = (int) $m->mo_estado;
                $activo = ($estado === 1);
                $retirado = ($estado === 3);

                $badgeClass = $activo ? 'badge-success' : ($retirado ? 'badge-secondary' : 'badge-danger');
                $badgeText  = $activo ? 'ACTIVO' : ($retirado ? 'RETIRADO' : 'INACTIVO');

                $descripcion = $activo
                  ? 'Este móvil está recibiendo servicios.'
                  : ($retirado
                      ? 'Este móvil fue retirado y no puede activarse.'
                      : 'Este móvil está fuera de servicio.');

                $cardBg = $retirado ? '#f1f3f5' : '#fff';
                $cardBorder = $retirado ? '1px solid #d1d5db' : '1px solid rgba(0,0,0,.08)';
                $cardOpacity = $retirado ? '0.75' : '1';

                $btnClass = $activo ? 'btn-danger' : 'btn-success';
                $btnIcon  = $activo ? 'mdi-power' : 'mdi-power-plug';
                $btnText  = $activo ? 'Desactivar' : 'Activar';
              @endphp

              <div class="col-md-6 col-lg-4 mb-4">
                <div class="p-3 border"
                     id="card-{{ $m->mo_id }}"
                     style="border-radius:16px; background:{{ $cardBg }}; border:{{ $cardBorder }}; opacity:{{ $cardOpacity }}; height:100%;">

                  {{-- CABECERA MÓVIL --}}
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted" style="font-size:13px;">MÓVIL</div>
                      <div style="font-size:28px;font-weight:800;letter-spacing:.5px; {{ $retirado ? 'color:#6b7280;' : '' }}">
                        {{ $m->mo_taxi }}
                      </div>
                    </div>

                    <div>
                      <span class="badge badge-pill {{ $badgeClass }}"
                            id="badge-{{ $m->mo_id }}"
                            data-estado="{{ $estado }}"
                            style="padding:10px 12px; font-size:12px;">
                        {{ $badgeText }}
                      </span>
                    </div>
                  </div>

                  {{-- DESCRIPCIÓN --}}
                  <div class="mt-3 {{ $retirado ? '' : 'text-muted' }}"
                       style="font-size:13px; {{ $retirado ? 'color:#6b7280;' : '' }}">
                    {{ $descripcion }}
                  </div>

                  {{-- BOTÓN --}}
                  <button
                    class="btn btn-block mt-3 {{ $retirado ? 'btn-secondary' : $btnClass }}"
                    id="btn-{{ $m->mo_id }}"
                    @if(!$retirado)
                      onclick="toggleMovil({{ $m->mo_id }})"
                    @endif
                    {{ $retirado ? 'disabled' : '' }}
                    style="border-radius:14px; padding:12px 14px; font-weight:800; {{ $retirado ? 'cursor:not-allowed; background:#9ca3af; border-color:#9ca3af;' : '' }}">

                    <i class="mdi {{ $retirado ? 'mdi-lock-outline' : $btnIcon }}"></i>
                    <span id="text-{{ $m->mo_id }}">
                      {{ $retirado ? 'No disponible' : $btnText }}
                    </span>
                  </button>

                  {{-- LOADING --}}
                  <div class="mt-2 text-muted" style="font-size:12px;">
                    <span id="load-{{ $m->mo_id }}" style="display:none;">
                      <i class="mdi mdi-loading mdi-spin"></i> Actualizando...
                    </span>
                  </div>

                </div>
              </div>
            @endforeach

          </div>
        @endif

      </div>
    </div>
  </div>
</div>

{{-- =================== MODALES =================== --}}

{{-- Modal: conductor inactivo (estado 2) --}}
<div class="modal fade" id="modalConductorInactivo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
      <div class="modal-header text-white"
           style="background: linear-gradient(135deg, #111827 0%, #6d28d9 60%, #2563eb 100%);">
        <h5 class="modal-title">⚠️ Activa tu estado</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-start">
          <i class="mdi mdi-account-alert-outline" style="font-size:28px;margin-right:10px;"></i>
          <div>
            <div style="font-weight:900;">No puedes activar el móvil.</div>
            <div class="text-muted" style="font-size:13px;">
              Primero debes activar tu <strong>estado de conductor</strong>.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:12px;">
          Cerrar
        </button>
        <a id="btnIrEstado" href="{{ url('/conductor/estado') }}"
           class="btn btn-primary" style="border-radius:12px;">
          Ir a Estado
        </a>
      </div>
    </div>
  </div>
</div>

{{-- Modal: conductor sancionado (estado 3 o 4) --}}
<div class="modal fade" id="modalConductorSancionado" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
      <div class="modal-header text-white"
           style="background: linear-gradient(135deg, #7f1d1d 0%, #ef4444 55%, #f97316 100%);">
        <h5 class="modal-title">⛔ Conductor sancionado</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-start">
          <i class="mdi mdi-alert-decagram" style="font-size:28px;margin-right:10px;"></i>
          <div>
            <div style="font-weight:900;">No puedes activar móviles.</div>
            <div class="text-muted" style="font-size:13px;">
              Debes acercarte a la <strong>oficina principal</strong>.
              Tu estado actual es <strong>Sancionado</strong>.
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:12px;">
          Entendido
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal: error genérico --}}
<div class="modal fade" id="modalErrorMovil" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="msgErrorMovil" class="mb-0">Ocurrió un error.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:12px;">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function toggleMovil(movilId) {
  const badge = document.getElementById('badge-' + movilId);
  const btn   = document.getElementById('btn-' + movilId);
  const text  = document.getElementById('text-' + movilId);
  const load  = document.getElementById('load-' + movilId);

  const estadoActual = Number(badge.dataset.estado || 0);

  // bloqueo extra en frontend para retirado
  if (estadoActual === 3) {
    document.getElementById('msgErrorMovil').textContent = 'Este móvil está retirado y no puede activarse.';
    $('#modalErrorMovil').modal('show');
    return;
  }

  const isActivo = badge.textContent.trim() === 'ACTIVO';
  const accion = isActivo ? 'desactivar' : 'activar';

  btn.disabled = true;
  load.style.display = 'inline';

  try {
    const res = await fetch(`{{ url('/conductor/moviles') }}/${movilId}/toggle`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ accion })
    });

    let json = {};
    try { json = await res.json(); } catch (e) { json = {}; }

    if (!res.ok || !json.success) {
      if (json.code === 'CONDUCTOR_INACTIVO') {
        if (json.redirect) document.getElementById('btnIrEstado').href = json.redirect;
        $('#modalConductorInactivo').modal('show');
        return;
      }

      if (json.code === 'CONDUCTOR_SANCIONADO') {
        $('#modalConductorSancionado').modal('show');
        return;
      }

      if (json.code === 'MOVIL_RETIRADO') {
        document.getElementById('msgErrorMovil').textContent = json.message || 'Este móvil está retirado y no puede activarse.';
        $('#modalErrorMovil').modal('show');
        return;
      }

      document.getElementById('msgErrorMovil').textContent = json.message || '❌ No se pudo cambiar el estado del móvil.';
      $('#modalErrorMovil').modal('show');
      return;
    }

    if (json.accion === 'activar') {
      location.reload();
      return;
    }

    badge.className = 'badge badge-pill badge-danger';
    badge.textContent = 'INACTIVO';
    badge.dataset.estado = '2';

    btn.className = 'btn btn-block mt-3 btn-success';
    btn.querySelector('i').className = 'mdi mdi-power-plug';
    text.textContent = 'Activar';

  } catch (e) {
    document.getElementById('msgErrorMovil').textContent = 'Error de red. Intenta nuevamente.';
    $('#modalErrorMovil').modal('show');
    console.error(e);
  } finally {
    btn.disabled = false;
    load.style.display = 'none';
  }
}
</script>
@endsection