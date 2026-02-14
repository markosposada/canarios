@extends('layouts.app')
@section('title','Anular / Levantar sanciones')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">ANULAR / LEVANTAR SANCIONES</h3>
    <p class="text-muted mb-0">Aquí no se borra nada: se marca como levantada y se guarda el motivo.</p>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
          <div>
            <h4 class="card-title mb-0">Sanciones recientes</h4>
            <small class="text-muted">En móvil se muestran como tarjetas.</small>
          </div>
          <button class="btn btn-sm btn-outline-primary mt-2 mt-sm-0" id="btnRefrescar" type="button">
            <i class="mdi mdi-refresh"></i> Refrescar
          </button>
        </div>

        {{-- ✅ Cards (mobile/tablet) --}}
        <div class="d-block d-md-none" id="cardsSanciones">
          <div class="text-muted text-center py-3">Cargando...</div>
        </div>

        {{-- ✅ Tabla (desktop) --}}
        <div class="table-responsive d-none d-md-block">
          <table class="table table-hover table-sm" id="tablaSanciones">
            <thead class="table-light">
              <tr>
                <th style="min-width:70px;">Móvil</th>
                <th style="min-width:220px;">Conductor</th>
                <th style="min-width:160px;">Tipo</th>
                <th style="min-width:150px;">Fecha</th>
                <th style="min-width:90px;">Oper.</th>
                <th style="min-width:120px;">Estado</th>
                <th style="min-width:120px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="7" class="text-muted text-center py-3">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- MODAL (Bootstrap 4) --}}
<div class="modal fade" id="modalLevantar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Levantar sanción</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="alert alert-warning py-2">
          Esta acción <strong>no elimina</strong> la sanción, solo la marca como levantada y guarda el motivo.
        </div>

        <input type="hidden" id="levantarId" value="">

        <div class="form-group mb-2">
          <label class="font-weight-bold mb-1">Motivo (obligatorio)</label>
          <textarea class="form-control" id="levantarMotivo" rows="3" maxlength="255"
                    placeholder="Ej: Se verificó y fue un error / Se solucionó el problema"></textarea>
          <small class="text-muted">Máximo 255 caracteres.</small>
        </div>

        <div id="msgLevantar"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btnCancelarLevantar">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmLevantar">Levantar</button>
      </div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlListar = "{{ route('operadora.sanciones.listar') }}";

  const btnRefrescar = document.getElementById('btnRefrescar');

  const tablaBody = document.querySelector('#tablaSanciones tbody');
  const cardsWrap = document.getElementById('cardsSanciones');

  const levantarId = document.getElementById('levantarId');
  const levantarMotivo = document.getElementById('levantarMotivo');
  const msgLevantar = document.getElementById('msgLevantar');
  const btnConfirmLevantar = document.getElementById('btnConfirmLevantar');

  function esc(str){
    if (str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function showMsgLevantar(html, cls='alert alert-info'){
    msgLevantar.innerHTML = `<div class="${cls} py-2">${html}</div>`;
  }

  function badgeEstadoHTML(r){
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    const vigente = parseInt(r.vigente ?? 0) === 1;

    if(!activa) return `<span class="badge badge-secondary">LEVANTADA</span>`;
    if(vigente) return `<span class="badge badge-success">VIGENTE</span>`;
    return `<span class="badge badge-danger">VENCIDA</span>`;
  }

  function btnAccionHTML(r){
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    if(!activa) return `<span class="text-muted">—</span>`;

    // Si tú ya estás calculando en backend si puede levantarse, perfecto:
    // si no, déjalo siempre activo.
    return `<button class="btn btn-sm btn-outline-success btnLevantar" data-id="${esc(r.sancion_id)}">
              Levantar
            </button>`;
  }

  function abrirModal(id){
    levantarId.value = id;
    levantarMotivo.value = '';
    msgLevantar.innerHTML = '';
    $('#modalLevantar').modal('show');
    setTimeout(()=> levantarMotivo.focus(), 200);
  }

  function cerrarModal(){
    $('#modalLevantar').modal('hide');
  }

  function attachHandlers(){
    document.querySelectorAll('.btnLevantar').forEach(btn => {
      btn.addEventListener('click', function(){
        abrirModal(this.getAttribute('data-id'));
      });
    });
  }

  function renderTabla(rows){
    if(!tablaBody) return;

    tablaBody.innerHTML = '';
    if(!rows.length){
      tablaBody.innerHTML = `<tr><td colspan="7" class="text-muted text-center py-3">Sin sanciones recientes.</td></tr>`;
      return;
    }

    rows.forEach(r => {
      tablaBody.insertAdjacentHTML('beforeend', `
        <tr>
          <td class="font-weight-bold">${esc(r.sancion_movil)}</td>
          <td>
            <div class="font-weight-bold">${esc(r.conductor)}</div>
            <small class="text-muted">CC ${esc(r.sancion_condu)}</small>
          </td>
          <td>
            <div>${esc(r.tipo)}</div>
            <small class="text-muted">${esc(r.horas)}h</small>
          </td>
          <td>
            <div>${esc(r.fecha)}</div>
            <small class="text-muted">${esc(r.hora)}</small>
          </td>
          <td>${esc(r.operadora)}</td>
          <td>${badgeEstadoHTML(r)}</td>
          <td>${btnAccionHTML(r)}</td>
        </tr>
      `);
    });
  }

  function renderCards(rows){
    if(!cardsWrap) return;

    if(!rows.length){
      cardsWrap.innerHTML = `<div class="text-muted text-center py-3">Sin sanciones recientes.</div>`;
      return;
    }

    cardsWrap.innerHTML = rows.map(r => {
      const activa = parseInt(r.sancion_activa ?? 1) === 1;

      return `
        <div class="card mb-2">
          <div class="card-body p-3">

            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="h5 mb-1">🚖 Móvil ${esc(r.sancion_movil)}</div>
                <div class="text-muted" style="font-size:12px;">
                  ${esc(r.fecha)} ${esc(r.hora)} · Oper: ${esc(r.operadora)}
                </div>
              </div>
              <div>${badgeEstadoHTML(r)}</div>
            </div>

            <hr class="my-2">

            <div style="font-size:13px;">
              <div class="mb-1">
                <strong>Conductor:</strong> ${esc(r.conductor)}
                <div class="text-muted" style="font-size:12px;">CC ${esc(r.sancion_condu)}</div>
              </div>

              <div class="mb-2">
                <strong>Tipo:</strong> ${esc(r.tipo)} <span class="text-muted">(${esc(r.horas)}h)</span>
              </div>

              <div class="d-flex justify-content-end">
                ${activa ? btnAccionHTML(r) : `<span class="text-muted">—</span>`}
              </div>
            </div>

          </div>
        </div>
      `;
    }).join('');
  }

  function cargarSanciones(){
    // placeholder
    if(cardsWrap) cardsWrap.innerHTML = `<div class="text-muted text-center py-3">Cargando...</div>`;
    if(tablaBody) tablaBody.innerHTML = `<tr><td colspan="7" class="text-muted text-center py-3">Cargando...</td></tr>`;

    fetch(urlListar, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(json => {
        const rows = json.data || [];
        renderTabla(rows);
        renderCards(rows);
        attachHandlers();
      })
      .catch(() => {
        if(cardsWrap) cardsWrap.innerHTML = `<div class="text-danger text-center py-3">Error cargando sanciones.</div>`;
        if(tablaBody) tablaBody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-3">Error cargando sanciones.</td></tr>`;
      });
  }

  btnRefrescar.addEventListener('click', cargarSanciones);

  btnConfirmLevantar.addEventListener('click', function(){
    const id = levantarId.value;
    const motivo = (levantarMotivo.value || '').trim();

    if(!id) return showMsgLevantar('No se encontró el ID.', 'alert alert-danger');
    if(!motivo) return showMsgLevantar('El motivo es obligatorio.', 'alert alert-warning');

    btnConfirmLevantar.disabled = true;

    const urlLevantar = "{{ url('/operadora/sanciones') }}/" + encodeURIComponent(id) + "/levantar";

    fetch(urlLevantar, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ motivo })
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.success) throw new Error(j.message || 'No se pudo levantar.');
      cerrarModal();
      cargarSanciones();
    })
    .catch(e => showMsgLevantar('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(() => btnConfirmLevantar.disabled = false);
  });

  // seguridad extra
  document.getElementById('btnCancelarLevantar')?.addEventListener('click', cerrarModal);

  cargarSanciones();
});
</script>
@endsection
