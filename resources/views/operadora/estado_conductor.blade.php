@extends('layouts.app')

@section('content')
<style>
  /* Puedes mover esto al layout */
  .page-wrap{ max-width: 1100px; }
  .page-title{ font-weight:800; letter-spacing:.2px; }

  .card-result{
    border:0;
    border-radius:14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
  }
  .mono{ font-variant-numeric: tabular-nums; }

  .btn-state{
    border-radius: 12px;
    font-weight: 700;
  }

  .muted-sm{ font-size: 12px; color:#6c757d; }

  .cell-ellipsis{
    max-width: 360px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0 page-title">ESTADO CONDUCTOR</h2>
    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm">← Regresar</a>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <label class="form-label fw-bold mb-1">Buscar conductor</label>

      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-9">
          <input type="text" id="qConductor" class="form-control"
                 placeholder="Buscar por nombre o cédula...">
          <div class="muted-sm mt-1">Escribe y se buscará automáticamente (mínimo 2 caracteres).</div>
        </div>
        <div class="col-12 col-md-3">
          <button type="button" class="btn btn-dark w-100" id="btnBuscar">Buscar</button>
        </div>
      </div>

      <div id="msg" class="mt-3"></div>
    </div>
  </div>

  {{-- MOBILE: Cards --}}
  <div class="d-md-none" id="listaCards">
    <div class="text-muted text-center py-3">Escribe para buscar...</div>
  </div>

  {{-- DESKTOP: Tabla --}}
  <div class="table-responsive d-none d-md-block">
    <table class="table table-sm table-striped align-middle" id="tablaConductores">
      <thead class="table-dark">
        <tr>
          <th style="width:140px;">Cédula</th>
          <th>Nombre</th>
          <th style="width:140px;">Estado</th>
          <th style="width:280px;">Acción</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="4" class="text-muted text-center">Escribe para buscar...</td></tr>
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlBuscar = "{{ route('operadora.estado_conductor.buscar') }}";
  const urlActualizar = "{{ route('operadora.estado_conductor.actualizar') }}";

  const qInput = document.getElementById('qConductor');
  const btnBuscar = document.getElementById('btnBuscar');

  const tbody = document.querySelector('#tablaConductores tbody'); // desktop
  const msg = document.getElementById('msg');
  const listaCards = document.getElementById('listaCards'); // mobile

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function estadoTexto(e){
    e = Number(e);
    if(e === 1) return 'ACTIVO';
    if(e === 2) return 'INACTIVO';
    if(e === 3) return 'SANCIONADO';
    if(e === 5) return 'EVADIDO';
    if(e === 4) return 'RETIRADO';
    return '—';
  }

  // ✅ Bootstrap 5 badge classes
  function badgeEstadoClass(e){
    e = Number(e);
    if(e === 1) return 'bg-success';
    if(e === 2) return 'bg-secondary';
    if(e === 3) return 'bg-warning text-dark';
    if(e === 4) return 'bg-dark';
    return 'bg-light text-dark';
  }

  function showMsg(html, cls='alert alert-info'){
    msg.innerHTML = `<div class="${cls} py-2 mb-0">${html}</div>`;
  }
  function clearMsg(){ msg.innerHTML = ''; }

  let t = null;

  function dispararBusqueda(){
    const q = qInput.value.trim();
    clearTimeout(t);

    if(q.length < 2){
      // desktop
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Escribe al menos 2 caracteres...</td></tr>`;
      // mobile
      listaCards.innerHTML = `<div class="text-muted text-center py-3">Escribe al menos 2 caracteres...</div>`;
      clearMsg();
      return;
    }

    t = setTimeout(()=> buscar(q), 250);
  }

  qInput.addEventListener('input', dispararBusqueda);
  btnBuscar.addEventListener('click', dispararBusqueda);

  function renderDesktop(rows){
    if(rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Sin resultados</td></tr>`;
      return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td class="mono">${esc(r.cedula)}</td>
          <td class="cell-ellipsis" title="${esc(r.nombre)}">${esc(r.nombre)}</td>
          <td><span class="badge ${badgeEstadoClass(r.estado)}">${esc(estadoTexto(r.estado))}</span></td>
          <td>
            <div class="d-flex flex-wrap" style="gap:6px;">
              <button class="btn btn-sm btn-success btnSet" data-cc="${esc(r.cedula)}" data-est="1">Activo</button>
              <button class="btn btn-sm btn-secondary btnSet" data-cc="${esc(r.cedula)}" data-est="2">Inactivo</button>
              <button class="btn btn-sm btn-warning btnSet" data-cc="${esc(r.cedula)}" data-est="3">Sanc.</button>
              <button class="btn btn-sm btn-warning btnSet" data-cc="${esc(r.cedula)}" data-est="5">Evadido</button>
              <button class="btn btn-sm btn-dark btnSet" data-cc="${esc(r.cedula)}" data-est="4">Ret.</button>
            </div>
          </td>
        </tr>
      `);
    });
  }

  function renderMobile(rows){
    if(rows.length === 0){
      listaCards.innerHTML = `<div class="text-muted text-center py-3">Sin resultados</div>`;
      return;
    }

    let html = '';
    rows.forEach(r => {
      const estado = estadoTexto(r.estado);
      const badge = badgeEstadoClass(r.estado);

      html += `
        <div class="card card-result mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div>
                <div class="fw-bold">${esc(r.nombre)}</div>
                <div class="muted-sm">Cédula: <span class="mono fw-semibold">${esc(r.cedula)}</span></div>
              </div>
              <div class="text-end">
                <span class="badge ${badge}">${esc(estado)}</span>
              </div>
            </div>

            <div class="row g-2 mt-3">
              <div class="col-6">
                <button class="btn btn-success w-100 btn-state btnSet" data-cc="${esc(r.cedula)}" data-est="1">Activo</button>
              </div>
              <div class="col-6">
                <button class="btn btn-secondary w-100 btn-state btnSet" data-cc="${esc(r.cedula)}" data-est="2">Inactivo</button>
              </div>
              <div class="col-6">
                <button class="btn btn-warning w-100 btn-state btnSet" data-cc="${esc(r.cedula)}" data-est="3">Sancionado</button>
              </div>
              <div class="col-6">
                <button class="btn btn-warning w-100 btn-state btnSet" data-cc="${esc(r.cedula)}" data-est="5">Evadido</button>
              </div>
              <div class="col-6">
                <button class="btn btn-dark w-100 btn-state btnSet" data-cc="${esc(r.cedula)}" data-est="4">Retirado</button>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    listaCards.innerHTML = html;
  }

  function bindAcciones(){
    // Delegación (evita re-bind mil veces)
    document.querySelectorAll('.btnSet').forEach(b => {
      b.addEventListener('click', function(){
        const cedula = this.getAttribute('data-cc');
        const estado = this.getAttribute('data-est');
        actualizarEstado(cedula, estado);
      });
    });
  }

  function buscar(q){
    // loaders
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Buscando...</td></tr>`;
    listaCards.innerHTML = `<div class="text-muted text-center py-3">Buscando...</div>`;
    clearMsg();

    fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}
    })
    .then(r => {
      if(r.status === 419) throw new Error('Sesión expirada (419)');
      return r.json();
    })
    .then(j => {
      const rows = j.data || [];
      renderDesktop(rows);
      renderMobile(rows);
      bindAcciones();
    })
    .catch((e) => {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error buscando</td></tr>`;
      listaCards.innerHTML = `<div class="text-danger text-center py-3">Error buscando</div>`;
      showMsg('❌ ' + (e.message || 'Error buscando'), 'alert alert-danger');
    });
  }

  function actualizarEstado(cedula, estado){
    Swal.fire({
      icon: 'question',
      title: 'Confirmar cambio',
      text: `¿Cambiar estado del conductor ${cedula} a ${estadoTexto(estado)}?`,
      showCancelButton: true,
      confirmButtonText: 'Sí, cambiar',
      cancelButtonText: 'Cancelar'
    }).then(res => {
      if(!res.isConfirmed) return;

      Swal.fire({ title:'Actualizando...', allowOutsideClick:false, didOpen: () => Swal.showLoading() });

      fetch(urlActualizar, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ cedula, estado: Number(estado) })
      })
      .then(async r => {
        const data = await r.json().catch(()=> ({}));
        if(r.status === 419) throw new Error('Sesión expirada (419)');
        if(!r.ok || !data.success) throw new Error(data.message || 'No se pudo actualizar');
        return data;
      })
      .then((data) => {
        Swal.close();
        showMsg(`✅ Estado actualizado: <b>${esc(cedula)}</b> → <b>${esc(estadoTexto(data.estado))}</b>`, 'alert alert-success');
        buscar(qInput.value.trim());
      })
      .catch(e => {
        Swal.close();
        showMsg('❌ ' + (e.message || 'Error actualizando'), 'alert alert-danger');
      });
    });
  }
});
</script>
@endsection
