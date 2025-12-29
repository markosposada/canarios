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
              @php $activo = ((int)$m->mo_estado === 1); @endphp

              <div class="col-md-6 col-lg-4 mb-4">
                <div class="p-3 border"
                     style="border-radius:16px; background:#fff; height:100%;">

                  {{-- CABECERA MÓVIL --}}
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="text-muted" style="font-size:13px;">MÓVIL</div>
                      <div style="font-size:28px;font-weight:800;letter-spacing:.5px;">
                        {{ $m->mo_taxi }}
                      </div>
                    </div>

                    <div>
                      <span class="badge badge-pill {{ $activo ? 'badge-success' : 'badge-danger' }}"
                            id="badge-{{ $m->mo_id }}"
                            style="padding:10px 12px; font-size:12px;">
                        {{ $activo ? 'ACTIVO' : 'INACTIVO' }}
                      </span>
                    </div>
                  </div>

                  {{-- DESCRIPCIÓN --}}
                  <div class="mt-3 text-muted" style="font-size:13px;">
                    {{ $activo
                      ? 'Este móvil está recibiendo servicios.'
                      : 'Este móvil está fuera de servicio.' }}
                  </div>

                  {{-- BOTÓN --}}
                  <button
                    class="btn btn-block mt-3 {{ $activo ? 'btn-danger' : 'btn-success' }}"
                    id="btn-{{ $m->mo_id }}"
                    onclick="toggleMovil({{ $m->mo_id }})"
                    style="border-radius:14px; padding:12px 14px; font-weight:800;">

                    <i class="mdi {{ $activo ? 'mdi-power' : 'mdi-power-plug' }}"></i>
                    <span id="text-{{ $m->mo_id }}">
                      {{ $activo ? 'Desactivar' : 'Activar' }}
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

    // ❌ Si el backend bloquea por reglas (403) o cualquier error
    if (!res.ok || !json.success) {

      // conductor estado 2 -> debe activar estado
      if (json.code === 'CONDUCTOR_INACTIVO') {
        if (json.redirect) document.getElementById('btnIrEstado').href = json.redirect;
        $('#modalConductorInactivo').modal('show');
        return;
      }

      // conductor estado 3 o 4 -> sancionado
      if (json.code === 'CONDUCTOR_SANCIONADO') {
        $('#modalConductorSancionado').modal('show');
        return;
      }

      // otro error
      document.getElementById('msgErrorMovil').textContent = json.message || '❌ No se pudo cambiar el estado del móvil.';
      $('#modalErrorMovil').modal('show');
      return;
    }

    // ✅ OK
    if (json.accion === 'activar') {
      // Recargamos para reflejar que los otros se desactivaron
      location.reload();
      return;
    }

    // Si solo se desactivó este
    badge.className = 'badge badge-pill badge-danger';
    badge.textContent = 'INACTIVO';
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
