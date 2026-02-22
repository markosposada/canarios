@extends('layouts.app_conductor')

@section('title', 'Mi Estado')

@section('content')
@php
  $estado = (int)($conductor->conduc_estado ?? 2);

  $esActivo = ($estado === 1);
  $esInactivo = ($estado === 2);
  $esSancionado = ($estado === 3);
  $esRetirado = ($estado === 4);
  $esEvadido = ($estado === 5);

  $bloqueado = ($esSancionado || $esRetirado || $esEvadido);
@endphp

<div class="row justify-content-center">
  <div class="col-md-10 col-lg-8">
    <div class="card shadow-sm border-0" style="border-radius:18px; overflow:hidden;">
      <div class="card-body p-0">

        {{-- Header con gradiente --}}
        <div class="p-4 text-white"
             style="background: linear-gradient(135deg, #1f3c88 0%, #3a7bd5 50%, #00d2ff 100%);">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <h4 class="mb-1">🚕 Mi Estado</h4>
              <div class="opacity-75">Conductor: <strong>{{ $conductor->conduc_nombres }}</strong></div>
              <div class="opacity-75">Cédula: <strong>{{ $conductor->conduc_cc }}</strong></div>
            </div>

            <div class="text-right">
              {{-- Badge según estado --}}
              <div class="badge badge-pill
                  {{ $esActivo ? 'badge-success' : ($esInactivo ? 'badge-danger' : 'badge-dark') }}"
                   id="estadoBadge"
                   style="font-size:14px;padding:10px 14px;">
                @if($esActivo) ACTIVO
                @elseif($esInactivo) INACTIVO
                @elseif($esSancionado) SANCIONADO
                @elseif($esRetirado) RETIRADO
                @elseif($esEvadido) EVADIDO
                @else DESCONOCIDO
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Cuerpo --}}
        <div class="p-4">
          <div class="row align-items-center">
            <div class="col-md-7">
              <h5 class="mb-2">¿Cómo funciona?</h5>
              <ul class="mb-0">
                <li>Si estás <strong>ACTIVO</strong>, quedas disponible para recibir servicios.</li>
                <li>Si estás <strong>INACTIVO</strong>, no deberían asignarte servicios.</li>
                <li>Si estás <strong>SANCIONADO</strong>, <strong>RETIRADO</strong> o <strong>EVADIDO</strong>, no puedes cambiar tu estado desde aquí.</li>
              </ul>

              {{-- Panel principal --}}
              <div class="mt-4 p-3"
                   style="border-radius:14px;background:#f7f9fc;border:1px solid #eef2f7;">
                <div class="d-flex align-items-center">
                  <i class="mdi
                    {{ $esActivo ? 'mdi-access-point' : ($esInactivo ? 'mdi-access-point-off' : 'mdi-alert-decagram') }}"
                     id="estadoIcon"
                     style="font-size:34px; margin-right:12px;"></i>

                  <div>
                    <div class="font-weight-bold" id="estadoTitle">
                      @if($esActivo) Estás en línea ✅
                      @elseif($esInactivo) Estás fuera de línea ⛔
                      @elseif($esSancionado) Estado: Sancionado ⛔
                      @elseif($esRetirado) Estado: Retirado ⛔
                      @elseif($esEvadido) Estado: Evadido ⛔
                      @else Estado no disponible
                      @endif
                    </div>

                    <div class="text-muted" id="estadoDesc" style="font-size:14px;">
                      @if($esActivo)
                        Puedes recibir servicios ahora mismo. Recuerda activar el movil para que te sean asignadas las carreras.
                      @elseif($esInactivo)
                        No recibirás asignaciones mientras estés inactivo.
                      @elseif($esSancionado)
                        Debes acercarte a la oficina principal para resolver tu situación.
                      @elseif($esRetirado)
                        Debes acercarte a la oficina principal. Tu estado figura como retirado.
                      @elseif($esEvadido)
                        Debes acercarte a la oficina principal para resolver tu situación.
                      @else
                        Comunícate con la oficina.
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              {{-- Aviso extra cuando está bloqueado --}}
              @if($bloqueado)
                <div class="mt-3 alert alert-warning" style="border-radius:14px;">
                  <strong>Acción requerida:</strong>
                  @if($esSancionado)
                    No puedes modificar tu estado porque estás <strong>SANCIONADO</strong>. Acércate a la oficina principal.
                  @elseif($esEvadido)
                    No puedes modificar tu estado porque estás <strong>EVADIDO</strong>. Acércate a la oficina principal.
                  @else
                    No puedes modificar tu estado porque estás <strong>RETIRADO</strong>. Acércate a la oficina principal.
                  @endif
                </div>
              @endif
            </div>

            <div class="col-md-5 mt-4 mt-md-0">
              {{-- Botón grande tipo “switch” --}}
              <div class="text-center">
                <div id="pulseWrap" class="{{ $esActivo ? 'pulse' : '' }}">
                  <button id="toggleBtn"
                          class="btn btn-lg btn-block
                                {{ $bloqueado ? 'btn-secondary' : ($esActivo ? 'btn-danger' : 'btn-success') }}"
                          style="border-radius:16px; padding:16px 18px; font-weight:700;"
                          {{ $bloqueado ? 'disabled' : '' }}>
                    <i class="mdi
                      {{ $bloqueado ? 'mdi-lock-outline' : ($esActivo ? 'mdi-power' : 'mdi-power-plug') }}"
                      style="font-size:20px;"></i>

                    <span id="btnText">
                      @if($bloqueado)
                        Estado bloqueado
                      @else
                        {{ $esActivo ? 'Ponerme INACTIVO' : 'Ponerme ACTIVO' }}
                      @endif
                    </span>
                  </button>
                </div>

                <div class="mt-3 text-muted" style="font-size:12px;">
                  <span id="saving" style="display:none;">
                    <i class="mdi mdi-loading mdi-spin"></i> Guardando...
                  </span>
                </div>
              </div>

              {{-- Decoración “semáforo” --}}
              <div class="mt-4">
                <div class="d-flex justify-content-center">
                  <div class="mx-2 text-center">
                    <div class="circle {{ $esActivo ? 'on-green' : 'off' }}"></div>
                    <small class="text-muted d-block mt-1">Activo</small>
                  </div>
                  <div class="mx-2 text-center">
                    <div class="circle {{ $esInactivo ? 'on-red' : 'off' }}"></div>
                    <small class="text-muted d-block mt-1">Inactivo</small>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>

    {{-- Modal confirmación --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar cambio</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="confirmText">...</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="confirmOk">Sí, cambiar</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal: Sancionado --}}
    <div class="modal fade" id="modalSancionado" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden;">
          <div class="modal-header text-white"
               style="background: linear-gradient(135deg, #7f1d1d 0%, #ef4444 55%, #f97316 100%);">
            <h5 class="modal-title">⛔ Estado: Sancionado</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Debes acercarte a la <strong>oficina principal</strong>. Tu estado se encuentra <strong>Sancionado</strong>.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Entendido</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal: Evadido --}}
    <div class="modal fade" id="modalEvadido" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden;">
          <div class="modal-header text-white"
               style="background: linear-gradient(135deg, #7c2d12 0%, #f97316 55%, #f59e0b 100%);">
            <h5 class="modal-title">⚠️ Estado: Evadido</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Debes acercarte a la <strong>oficina principal</strong>. Tu estado se encuentra <strong>Evadido</strong>.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Entendido</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal: Retirado --}}
    <div class="modal fade" id="modalRetirado" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px; overflow:hidden;">
          <div class="modal-header text-white"
               style="background: linear-gradient(135deg, #111827 0%, #6b7280 55%, #374151 100%);">
            <h5 class="modal-title">🚫 Estado: Retirado</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Debes acercarte a la <strong>oficina principal</strong>. Tu estado se encuentra <strong>Retirado</strong>.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Entendido</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal: Error genérico --}}
    <div class="modal fade" id="modalErrorEstado" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:18px;">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title">Error</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id="msgErrorEstado">Ocurrió un error.</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
  .circle{width:18px;height:18px;border-radius:50%;border:1px solid #e5e7eb;background:#f3f4f6;}
  .on-green{background:#16a34a;border-color:#16a34a; box-shadow:0 0 12px rgba(22,163,74,.45);}
  .on-red{background:#dc2626;border-color:#dc2626; box-shadow:0 0 12px rgba(220,38,38,.45);}
  .off{opacity:.35}

  .pulse{position:relative;}
  .pulse:before{
    content:'';
    position:absolute;
    inset:-10px;
    border-radius:18px;
    border:2px solid rgba(22,163,74,.35);
    animation:pulse 1.7s infinite;
    pointer-events:none;
  }
  @keyframes pulse{
    0%{transform:scale(.98); opacity:.6;}
    70%{transform:scale(1.06); opacity:0;}
    100%{opacity:0;}
  }
</style>
@endsection

@section('scripts')
<script>
  let estadoActual = {{ (int)$conductor->conduc_estado }};
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  const toggleBtn = document.getElementById('toggleBtn');
  const btnText = document.getElementById('btnText');
  const saving = document.getElementById('saving');

  const estadoBadge = document.getElementById('estadoBadge');
  const estadoIcon = document.getElementById('estadoIcon');
  const estadoTitle = document.getElementById('estadoTitle');
  const estadoDesc = document.getElementById('estadoDesc');
  const pulseWrap = document.getElementById('pulseWrap');

  const confirmText = document.getElementById('confirmText');
  const confirmOk = document.getElementById('confirmOk');

  let nextEstado = null;

  function renderUI(estado){
    estado = parseInt(estado);

    const esActivo = (estado === 1);
    const esInactivo = (estado === 2);
    const esSancionado = (estado === 3);
    const esRetirado = (estado === 4);
    const esEvadido = (estado === 5);

    // Badge
    if(esActivo){
      estadoBadge.className = 'badge badge-pill badge-success';
      estadoBadge.textContent = 'ACTIVO';
    } else if(esInactivo){
      estadoBadge.className = 'badge badge-pill badge-danger';
      estadoBadge.textContent = 'INACTIVO';
    } else if(esSancionado){
      estadoBadge.className = 'badge badge-pill badge-dark';
      estadoBadge.textContent = 'SANCIONADO';
    } else if(esRetirado){
      estadoBadge.className = 'badge badge-pill badge-dark';
      estadoBadge.textContent = 'RETIRADO';
    } else if(esEvadido){
      estadoBadge.className = 'badge badge-pill badge-dark';
      estadoBadge.textContent = 'EVADIDO';
    } else {
      estadoBadge.className = 'badge badge-pill badge-dark';
      estadoBadge.textContent = 'DESCONOCIDO';
    }

    // Panel
    estadoIcon.className = 'mdi ' + (esActivo ? 'mdi-access-point' : (esInactivo ? 'mdi-access-point-off' : 'mdi-alert-decagram'));

    estadoTitle.textContent =
      esActivo ? 'Estás en línea ✅'
      : esInactivo ? 'Estás fuera de línea ⛔'
      : esSancionado ? 'Estado: Sancionado ⛔'
      : esRetirado ? 'Estado: Retirado ⛔'
      : esEvadido ? 'Estado: Evadido ⛔'
      : 'Estado no disponible';

    estadoDesc.textContent =
      esActivo ? 'Puedes recibir servicios ahora mismo. Recuerda activar el movil para que te sean asignadas las carreras'
      : esInactivo ? 'No recibirás asignaciones mientras estés inactivo.'
      : (esSancionado || esEvadido) ? 'Debes acercarte a la oficina principal para resolver tu situación.'
      : esRetirado ? 'Debes acercarte a la oficina principal. Tu estado figura como retirado.'
      : 'Comunícate con la oficina.';

    // Botón solo para 1/2
    if(esActivo || esInactivo){
      toggleBtn.disabled = false;
      toggleBtn.className = 'btn btn-lg btn-block ' + (esActivo ? 'btn-danger' : 'btn-success');
      btnText.textContent = esActivo ? 'Ponerme INACTIVO' : 'Ponerme ACTIVO';
      pulseWrap.className = esActivo ? 'pulse' : '';
      toggleBtn.querySelector('i').className = 'mdi ' + (esActivo ? 'mdi-power' : 'mdi-power-plug');
    } else {
      toggleBtn.disabled = true;
      toggleBtn.className = 'btn btn-lg btn-block btn-secondary';
      btnText.textContent = 'Estado bloqueado';
      pulseWrap.className = '';
      toggleBtn.querySelector('i').className = 'mdi mdi-lock-outline';
    }
  }

  async function guardarEstado(estado){
    saving.style.display = 'inline';
    toggleBtn.disabled = true;

    try{
      const res = await fetch("{{ route('conductor.estado.update') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ estado })
      });

      let json = {};
      try { json = await res.json(); } catch(e){ json = {}; }

      if (!res.ok || !json.success) {
        if (json.code === 'CONDUCTOR_SANCIONADO') { $('#modalSancionado').modal('show'); return; }
        if (json.code === 'CONDUCTOR_RETIRADO')   { $('#modalRetirado').modal('show'); return; }
        if (json.code === 'CONDUCTOR_EVADIDO')    { $('#modalEvadido').modal('show'); return; }

        document.getElementById('msgErrorEstado').textContent = json.message || 'No se pudo actualizar tu estado.';
        $('#modalErrorEstado').modal('show');
        return;
      }

      estadoActual = parseInt(json.estado);
      renderUI(estadoActual);

    }catch(e){
      document.getElementById('msgErrorEstado').textContent = 'No se pudo actualizar tu estado. Intenta de nuevo.';
      $('#modalErrorEstado').modal('show');
      console.error(e);
    }finally{
      saving.style.display = 'none';
      toggleBtn.disabled = false;
    }
  }

  // Click botón
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      // Estados bloqueados => mostrar modal correspondiente
      if (estadoActual === 3) { $('#modalSancionado').modal('show'); return; }
      if (estadoActual === 4) { $('#modalRetirado').modal('show'); return; }
      if (estadoActual === 5) { $('#modalEvadido').modal('show'); return; }

      const activo = (estadoActual === 1);
      nextEstado = activo ? 2 : 1;

      confirmText.innerHTML = activo
        ? 'Vas a ponerte <strong>INACTIVO</strong>. No deberían asignarte servicios mientras estés fuera de línea.'
        : 'Vas a ponerte <strong>ACTIVO</strong>. Quedarás disponible para recibir servicios.';

      $('#confirmModal').modal('show');
    });
  }

  confirmOk.addEventListener('click', async () => {
    $('#confirmModal').modal('hide');
    await guardarEstado(nextEstado);
  });

  // Inicial
  renderUI(estadoActual);
</script>
@endsection