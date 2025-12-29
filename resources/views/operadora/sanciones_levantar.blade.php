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

        <div class="d-flex align-items-center justify-content-between">
          <h4 class="card-title mb-0">Sanciones recientes</h4>
          <div class="d-flex" style="gap:8px;">
            <button class="btn btn-sm btn-outline-primary" id="btnRefrescar" type="button">Refrescar</button>
            <a href="{{ route('operadora.sancionar') }}" class="btn btn-sm btn-outline-secondary">Ir a Sancionar</a>
          </div>
        </div>

        <div class="table-responsive mt-3">
          <table class="table table-striped" id="tablaSanciones">
            <thead>
              <tr>
                <th style="width:60px;">#</th>
                <th>Móvil</th>
                <th>Conductor</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Operadora</th>
                <th>Estado</th>
                <th style="width:120px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="8" class="text-muted text-center">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <style>
          .badge-soft{ padding:.35rem .55rem; border-radius:999px; font-weight:800; font-size:12px; display:inline-block; }
          .badge-vigente{ background:#d1fae5; color:#065f46; border:1px solid #10b981; }
          .badge-vencida{ background:#fee2e2; color:#991b1b; border:1px solid #ef4444; }
          .badge-levantada{ background:#e5e7eb; color:#111827; border:1px solid #9ca3af; }
          .small-muted{ font-size:12px; color:#6c757d; }
        </style>

      </div>
    </div>
  </div>
</div>

{{-- Modal: Levantar sanción --}}
<div class="modal fade" id="modalLevantar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Levantar sanción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2">
          Esta acción <strong>no elimina</strong> la sanción, solo la marca como levantada y guarda el motivo.
        </div>

        <input type="hidden" id="levantarId" value="">

        <div class="mb-2">
          <label class="form-label fw-bold">Motivo (obligatorio)</label>
          <textarea class="form-control" id="levantarMotivo" rows="3" maxlength="255"
                    placeholder="Ej: Se verificó y fue un error / Se solucionó el problema"></textarea>
          <div class="small-muted mt-1">Máximo 255 caracteres.</div>
        </div>

        <div id="msgLevantar"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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

  const modalEl = document.getElementById('modalLevantar');
  const levantarId = document.getElementById('levantarId');
  const levantarMotivo = document.getElementById('levantarMotivo');
  const msgLevantar = document.getElementById('msgLevantar');
  const btnConfirmLevantar = document.getElementById('btnConfirmLevantar');

  let bsModal = null;

  function escapeHtml(str){
    if (str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showMsgLevantar(html, cls='alert alert-info') {
    msgLevantar.innerHTML = `<div class="${cls} py-2">${html}</div>`;
    setTimeout(()=>{ msgLevantar.innerHTML=''; }, 3500);
  }

  function badgeEstado(r) {
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    const vigente = parseInt(r.vigente ?? 0) === 1;

    if (!activa) {
      const extra = (r.sancion_levantada_fecha || r.sancion_levantada_hora)
        ? `<div class="small-muted">Levantada: ${escapeHtml(r.sancion_levantada_fecha || '')} ${escapeHtml(r.sancion_levantada_hora || '')}</div>
           <div class="small-muted">Por: ${escapeHtml(r.sancion_levantada_operadora || '')}</div>`
        : '';
      return `<span class="badge-soft badge-levantada">LEVANTADA</span>${extra}`;
    }

    if (vigente) {
      return `<span class="badge-soft badge-vigente">VIGENTE (${escapeHtml(r.minutos_restantes)} min)</span>`;
    }

    return `<span class="badge-soft badge-vencida">VENCIDA</span>`;
  }

  function btnAcciones(r) {
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    if (!activa) return `<span class="text-muted">—</span>`;
    return `<button class="btn btn-sm btn-outline-success btn-levantar" data-id="${r.sancion_id}">Levantar</button>`;
  }

  function attachLevantarHandlers() {
    document.querySelectorAll('.btn-levantar').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        if (!id) return;

        levantarId.value = id;
        levantarMotivo.value = '';
        msgLevantar.innerHTML = '';

        if (window.bootstrap && window.bootstrap.Modal) {
          bsModal = bsModal || new bootstrap.Modal(modalEl);
          bsModal.show();
        } else {
          alert('Bootstrap modal no está disponible en este layout.');
        }
      });
    });
  }

  function cargarSanciones() {
    fetch(urlListar, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(json => {
        const rows = json.data || [];
        const tbody = document.querySelector('#tablaSanciones tbody');
        tbody.innerHTML = '';

        if (!rows.length) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-muted text-center">Sin sanciones recientes.</td></tr>`;
          return;
        }

        rows.forEach((r, idx) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="fw-bold">${idx+1}</td>
            <td>${escapeHtml(r.sancion_movil)}</td>
            <td>
              ${escapeHtml(r.conductor)}
              <div class="small-muted">CC ${escapeHtml(r.sancion_condu)}</div>
            </td>
            <td>
              ${escapeHtml(r.tipo)} <span class="text-muted">(${escapeHtml(r.horas)}h)</span>
              ${r.sancion_levantada_motivo ? `<div class="small-muted">Motivo: ${escapeHtml(r.sancion_levantada_motivo)}</div>` : ''}
            </td>
            <td>${escapeHtml(r.fecha)} ${escapeHtml(r.hora)}</td>
            <td>${escapeHtml(r.operadora || '')}</td>
            <td>${badgeEstado(r)}</td>
            <td>${btnAcciones(r)}</td>
          `;
          tbody.appendChild(tr);
        });

        attachLevantarHandlers();
      })
      .catch(() => {
        const tbody = document.querySelector('#tablaSanciones tbody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center">Error cargando sanciones.</td></tr>`;
      });
  }

  btnRefrescar.addEventListener('click', cargarSanciones);

  btnConfirmLevantar.addEventListener('click', function () {
    const id = levantarId.value;
    const motivo = (levantarMotivo.value || '').trim();

    if (!id) return showMsgLevantar('No se encontró el ID de la sanción.', 'alert alert-danger');
    if (!motivo) return showMsgLevantar('El motivo es obligatorio.', 'alert alert-warning');

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
      if (!ok || !j.success) throw new Error(j.message || 'No se pudo levantar la sanción.');
      showMsgLevantar('✅ Sanción levantada.', 'alert alert-success');
      if (bsModal) bsModal.hide();
      cargarSanciones();
    })
    .catch(e => showMsgLevantar('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(() => btnConfirmLevantar.disabled = false);
  });

  cargarSanciones();
});
</script>
@endsection
