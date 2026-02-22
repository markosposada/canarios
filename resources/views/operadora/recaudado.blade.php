@extends('layouts.app')
@section('title','Recaudado')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">RECAUDADO (PAGOS)</h3>
    <p class="text-muted mb-0">Selecciona un conductor y registra el pago de lo facturado pendiente.</p>
  </div>
</div>

<style>
  .tabla-fixed { table-layout: fixed; width: 100%; }
  .td-ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .col-fecha { width: 110px; }
  .col-hora  { width: 85px; }
  .col-total { width: 120px; text-align:right; font-weight: 800; }
  .col-check { width: 44px; text-align:center; }
  .kpi-box { border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }

  /* modal simple */
  .modal-backdrop-custom{
    position: fixed; inset:0; background: rgba(0,0,0,.45);
    display:none; align-items:center; justify-content:center; z-index: 1050;
    padding: 16px;
  }
  .modal-card{
    width: 100%; max-width: 520px; background:#fff; border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    overflow:hidden; border: 1px solid rgba(0,0,0,.08);
  }
  .modal-head{
    padding: 14px 16px;
    border-bottom:1px solid rgba(0,0,0,.08);
    display:flex; align-items:center; justify-content:space-between;
  }
  .modal-body{ padding: 14px 16px; }

  .result-item{
    padding: 10px 12px;
    border:1px solid rgba(0,0,0,.08);
    border-radius: 12px;
    margin-bottom: 10px;
    cursor:pointer;
  }
  .result-item:hover{ background: rgba(0,0,0,.03); }
  .small-muted{ font-size:12px; color:#6c757d; }
</style>

<div class="row">
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Seleccionar conductor</h4>

        <button class="btn btn-primary w-100" id="btnModalConductor" type="button">
          <i class="mdi mdi-account-search mr-1"></i> Buscar conductor
        </button>

        <div id="msg" class="mt-3"></div>

        <hr>

        <div id="info" style="display:none;">
          <p class="mb-1"><strong>Móvil:</strong> <span id="infoMo"></span></p>
          <p class="mb-1"><strong>Conductor:</strong> <span id="infoConductor"></span></p>
          <p class="mb-1"><strong>Cédula:</strong> <span id="infoCc"></span></p>
        </div>

        <div class="mt-3">
          <label class="form-label fw-bold mb-1">Método</label>
          <select id="metodo" class="form-control">
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="TRANSFERENCIA">TRANSFERENCIA</option>
            <option value="DATAFONO">DATAFONO</option>
          </select>

          <label class="form-label fw-bold mt-3 mb-1">Observación (opcional)</label>
          <input id="obs" class="form-control" maxlength="255" placeholder="Ej: Pago completo">
        </div>

        <button class="btn btn-success w-100 mt-3" id="btnPagar" type="button" disabled>
          REGISTRAR PAGO
        </button>
      </div>
    </div>
  </div>

  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Facturas pendientes</h4>

        <div class="row mb-2">
          <div class="col-md-4">
            <div class="p-3 kpi-box">
              <strong>Total pendiente:</strong>
              $<span id="totalPendiente">0</span>
            </div>
          </div>
          <div class="col-md-8 text-md-right mt-2 mt-md-0">
            <button class="btn btn-outline-secondary btn-sm" id="btnMarcarTodo" type="button" disabled>
              Marcar todo
            </button>
            <button class="btn btn-outline-secondary btn-sm" id="btnDesmarcarTodo" type="button" disabled>
              Desmarcar
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-striped tabla-fixed" id="tablaFacturas">
            <thead>
              <tr>
                <th class="col-check"><input type="checkbox" id="checkAll"></th>
                <th>Factura</th>
                <th class="col-fecha">Fecha</th>
                <th class="col-hora">Hora</th>
                <th class="col-total">Total</th>
                <th style="width:90px;">Ver</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="6" class="text-muted text-center">Selecciona un conductor</td></tr>
            </tbody>
          </table>
        </div>

        <div class="alert alert-info py-2 mt-3 mb-0">
          <strong>Seleccionado a pagar:</strong> $<span id="totalSeleccionado">0</span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL BUSCAR CONDUCTOR --}}
<div id="modalBack" class="modal-backdrop-custom" role="dialog" aria-modal="true">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <strong>Buscar conductor</strong><br>
        <span class="small-muted">Escribe nombre o cédula</span>
      </div>
      <button class="btn btn-sm btn-light" id="btnCerrarModal" type="button">✕</button>
    </div>
    <div class="modal-body">
      <input type="text" class="form-control" id="buscadorConductor" placeholder="Ej: ADAN o 9090">
      <div class="small-muted mt-2">Resultados:</div>
      <div id="resultados" class="mt-2"></div>
      <div class="small-muted mt-2">
        Al seleccionar un conductor, se cargan sus facturas pendientes automáticamente.
      </div>
    </div>
  </div>
</div>

{{-- MODAL DETALLE FACTURA --}}
<div id="modalDetBack" class="modal-backdrop-custom" role="dialog" aria-modal="true">
  <div class="modal-card" style="max-width: 900px;">
    <div class="modal-head">
      <div>
        <strong>Detalle factura</strong><br>
        <span class="small-muted" id="detTitulo">—</span>
      </div>
      <button class="btn btn-sm btn-light" id="btnCerrarDet" type="button">✕</button>
    </div>
    <div class="modal-body">
      <div id="detBody" class="small-muted">Cargando...</div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const urlPendCc       = "{{ route('operadora.recaudado.pendientes_cc') }}";
  const urlBuscarCon    = "{{ route('operadora.recaudado.buscar_conductores') }}";
  const urlPagar        = "{{ route('operadora.recaudado.pagar') }}";
  const urlDetFacturaTpl= "{{ route('operadora.recaudado.factura_detalle', ['id' => '__ID__']) }}";

  const btnPagar = document.getElementById('btnPagar');
  const msg = document.getElementById('msg');

  const info = document.getElementById('info');
  const infoMo = document.getElementById('infoMo');
  const infoConductor = document.getElementById('infoConductor');
  const infoCc = document.getElementById('infoCc');

  const totalPendiente = document.getElementById('totalPendiente');
  const totalSeleccionado = document.getElementById('totalSeleccionado');

  const tBody = document.querySelector('#tablaFacturas tbody');
  const checkAll = document.getElementById('checkAll');
  const btnMarcarTodo = document.getElementById('btnMarcarTodo');
  const btnDesmarcarTodo = document.getElementById('btnDesmarcarTodo');

  const metodo = document.getElementById('metodo');
  const obs = document.getElementById('obs');

  // Modal conductor
  const modalBack = document.getElementById('modalBack');
  const btnModalConductor = document.getElementById('btnModalConductor');
  const btnCerrarModal = document.getElementById('btnCerrarModal');
  const buscadorConductor = document.getElementById('buscadorConductor');
  const resultados = document.getElementById('resultados');

  // Modal detalle
  const modalDetBack = document.getElementById('modalDetBack');
  const btnCerrarDet = document.getElementById('btnCerrarDet');
  const detBody = document.getElementById('detBody');
  const detTitulo = document.getElementById('detTitulo');

  let facturasCache = [];
  let currentCedula = null;

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function showMsg(html, cls='alert alert-info'){
    msg.innerHTML = `<div class="${cls} py-2">${html}</div>`;
    setTimeout(()=>msg.innerHTML='', 4000);
  }

  function calcSeleccionado(){
    const checks = Array.from(document.querySelectorAll('.chk-factura'));
    let total = 0;
    checks.forEach(ch => {
      if(ch.checked){
        const id = parseInt(ch.value, 10);
        const f = facturasCache.find(x => Number(x.fo_id) === id);
        if(f) total += Number(f.fo_total || 0);
      }
    });
    totalSeleccionado.textContent = String(total);
    btnPagar.disabled = total <= 0;
  }

  // ====== MODAL DETALLE ======
  function abrirDet(){ modalDetBack.style.display = 'flex'; }
  function cerrarDet(){ modalDetBack.style.display = 'none'; }
  btnCerrarDet.addEventListener('click', cerrarDet);
  modalDetBack.addEventListener('click', function(e){
    if(e.target === modalDetBack) cerrarDet();
  });

  function verFactura(id){
    detTitulo.textContent = `Factura #${id}`;
    detBody.innerHTML = 'Cargando...';
    abrirDet();

    const url = urlDetFacturaTpl.replace('__ID__', encodeURIComponent(id));

    fetch(url, { headers: {'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json'} })
      .then(r => r.json().then(j => ({ok:r.ok, j})))
      .then(({ok, j}) => {
        if(!ok || !j.ok) throw new Error(j.message || 'No se pudo cargar el detalle.');

        const f = j.factura || {};
        const serv = j.servicios || [];
        const sanc = j.sanciones || [];

        let html = `
          <div class="mb-3">
            <div class="row g-2">
              <div class="col-12 col-md-3"><div class="p-2 border rounded"><div class="small-muted">Móvil</div><div class="fw-bold">${esc(f.fo_movil)}</div></div></div>
              <div class="col-12 col-md-3"><div class="p-2 border rounded"><div class="small-muted">Conductor</div><div class="fw-bold">${esc(f.fo_conductor)}</div></div></div>
              <div class="col-12 col-md-3"><div class="p-2 border rounded"><div class="small-muted">Fecha</div><div class="fw-bold">${esc(f.fo_fecha)}</div></div></div>
              <div class="col-12 col-md-3"><div class="p-2 border rounded"><div class="small-muted">Hora</div><div class="fw-bold">${esc(f.fo_hora)}</div></div></div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-12 col-md-4"><div class="p-2 border rounded"><div class="small-muted">Total servicios</div><div class="fw-bold">$${esc(f.fo_total_servicios)}</div></div></div>
              <div class="col-12 col-md-4"><div class="p-2 border rounded"><div class="small-muted">Total sanciones</div><div class="fw-bold">$${esc(f.fo_total_sanciones)}</div></div></div>
              <div class="col-12 col-md-4"><div class="p-2 border rounded"><div class="small-muted">Total factura</div><div class="fw-bold">$${esc(f.fo_total)}</div></div></div>
            </div>
          </div>

          <hr class="mt-0"/>

          <h6 class="mb-2">Servicios (${serv.length})</h6>
        `;

        if(serv.length === 0){
          html += `<div class="text-muted">No hay servicios.</div>`;
        } else {
          html += `<div class="table-responsive"><table class="table table-sm table-striped">
            <thead>
              <tr>
                <th style="width:110px;">Fecha</th>
                <th style="width:85px;">Hora</th>
                <th>Dirección</th>
                <th class="text-right" style="width:110px;">Valor</th>
              </tr>
            </thead><tbody>`;

          serv.forEach(s=>{
            const dir = String(s.direccion || '—');
            const dir20 = dir.length > 20 ? dir.slice(0, 20) + '…' : dir;
            html += `<tr>
              <td>${esc(s.fecha || '—')}</td>
              <td>${esc(s.hora || '—')}</td>
              <td title="${esc(dir)}">${esc(dir20)}</td>
              <td class="text-right">$${esc(s.valor)}</td>
            </tr>`;
          });

          html += `</tbody></table></div>`;
        }

        html += `<hr/><h6 class="mb-2">Sanciones (${sanc.length})</h6>`;

        if(sanc.length === 0){
          html += `<div class="text-muted">No hay sanciones.</div>`;
        } else {
          html += `<div class="table-responsive"><table class="table table-sm table-striped">
            <thead>
              <tr>
                <th style="width:110px;">Fecha</th>
                <th style="width:85px;">Hora</th>
                <th>Tipo</th>
                <th class="text-right" style="width:110px;">Valor</th>
              </tr>
            </thead><tbody>`;

          sanc.forEach(s=>{
            html += `<tr>
              <td>${esc(s.fecha || '—')}</td>
              <td>${esc(s.hora || '—')}</td>
              <td>${esc(s.tipo || '—')}</td>
              <td class="text-right">$${esc(s.valor)}</td>
            </tr>`;
          });

          html += `</tbody></table></div>`;
        }

        detBody.innerHTML = html;
      })
      .catch(e => {
        detBody.innerHTML = `<div class="text-danger">❌ ${esc(e.message || 'Error')}</div>`;
      });
  }

  // ====== RENDER FACTURAS ======
  function render(data){
    info.style.display = 'block';
    infoMo.textContent = data.movil.mo_taxi;
    infoConductor.textContent = data.movil.conductor_nombre;
    infoCc.textContent = data.movil.conductor_cc;

    currentCedula = String(data.movil.conductor_cc || '');

    facturasCache = data.facturas || [];
    totalPendiente.textContent = String(data.total_pendiente || 0);

    tBody.innerHTML = '';
    checkAll.checked = false;

    btnMarcarTodo.disabled = facturasCache.length === 0;
    btnDesmarcarTodo.disabled = facturasCache.length === 0;

    if (facturasCache.length === 0) {
      tBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">No hay facturas pendientes</td></tr>`;
      totalSeleccionado.textContent = '0';
      btnPagar.disabled = true;
      return;
    }

    facturasCache.forEach(f => {
      tBody.insertAdjacentHTML('beforeend', `
        <tr>
          <td class="col-check text-center">
            <input class="chk-factura" type="checkbox" value="${esc(f.fo_id)}">
          </td>
          <td class="td-ellipsis" title="Operadora: ${esc(f.fo_operadora)} | Serv: $${esc(f.fo_total_servicios)} | Sanc: $${esc(f.fo_total_sanciones)}">
            #${esc(f.fo_id)}
          </td>
          <td>${esc(f.fo_fecha)}</td>
          <td>${esc(f.fo_hora)}</td>
          <td class="col-total">$${esc(f.fo_total)}</td>
          <td class="text-right">
            <button type="button" class="btn btn-sm btn-outline-primary btnVer" data-id="${esc(f.fo_id)}">Ver</button>
          </td>
        </tr>
      `);
    });

    document.querySelectorAll('.chk-factura').forEach(ch => {
      ch.addEventListener('change', () => {
        const all = Array.from(document.querySelectorAll('.chk-factura'));
        checkAll.checked = all.length > 0 && all.every(x => x.checked);
        calcSeleccionado();
      });
    });

    document.querySelectorAll('.btnVer').forEach(b => {
      b.addEventListener('click', function(){
        verFactura(this.getAttribute('data-id'));
      });
    });

    calcSeleccionado();
  }

  function fetchPendientesPorCedula(cedula){
    btnPagar.disabled = true;
    tBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">Cargando...</td></tr>`;

    fetch(urlPendCc + '?cedula=' + encodeURIComponent(cedula), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'Error consultando.');
      render(j);
    })
    .catch(e => {
      info.style.display = 'none';
      tBody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Error cargando</td></tr>`;
      showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    });
  }

  // ====== PAGAR ======
  checkAll.addEventListener('change', function(){
    document.querySelectorAll('.chk-factura').forEach(ch => ch.checked = checkAll.checked);
    calcSeleccionado();
  });

  btnMarcarTodo.addEventListener('click', function(){
    checkAll.checked = true;
    document.querySelectorAll('.chk-factura').forEach(ch => ch.checked = true);
    calcSeleccionado();
  });

  btnDesmarcarTodo.addEventListener('click', function(){
    checkAll.checked = false;
    document.querySelectorAll('.chk-factura').forEach(ch => ch.checked = false);
    calcSeleccionado();
  });

  btnPagar.addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.chk-factura'))
      .filter(ch => ch.checked)
      .map(ch => parseInt(ch.value, 10))
      .filter(n => Number.isFinite(n) && n > 0);

    if(ids.length === 0) return;
    if(!confirm('¿Confirmas registrar el pago de ' + ids.length + ' factura(s)?')) return;

    btnPagar.disabled = true;

    fetch(urlPagar, {
      method:'POST',
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        facturas: ids,
        metodo: metodo.value || 'EFECTIVO',
        observacion: obs.value || null
      })
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'No se pudo registrar el pago.');
      showMsg('✅ Pago registrado. Total pagado: $' + j.total_pagado, 'alert alert-success');

      if(currentCedula) fetchPendientesPorCedula(currentCedula);
    })
    .catch(e => showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(()=> btnPagar.disabled = false);
  });

  // ====== MODAL CONDUCTOR ======
  function abrirModal(){
    modalBack.style.display = 'flex';
    resultados.innerHTML = '';
    buscadorConductor.value = '';
    setTimeout(()=> buscadorConductor.focus(), 50);
  }
  function cerrarModal(){ modalBack.style.display = 'none'; }

  btnModalConductor.addEventListener('click', abrirModal);
  btnCerrarModal.addEventListener('click', cerrarModal);
  modalBack.addEventListener('click', function(e){
    if(e.target === modalBack) cerrarModal();
  });

  let tmr = null;
  buscadorConductor.addEventListener('input', function(){
    clearTimeout(tmr);
    const q = buscadorConductor.value.trim();
    if(q.length < 2){
      resultados.innerHTML = `<div class="small-muted">Escribe al menos 2 caracteres...</div>`;
      return;
    }

    resultados.innerHTML = `<div class="small-muted">Buscando...</div>`;

    tmr = setTimeout(() => {
      fetch(urlBuscarCon + '?q=' + encodeURIComponent(q), {
        headers: {'X-Requested-With':'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(j => {
        const rows = j.data || [];
        if(rows.length === 0){
          resultados.innerHTML = `<div class="small-muted">No hay resultados.</div>`;
          return;
        }

        resultados.innerHTML = '';
        rows.forEach(r => {
          resultados.insertAdjacentHTML('beforeend', `
            <div class="result-item" data-cc="${esc(r.cedula)}">
              <div><strong>${esc(r.nombre)}</strong></div>
              <div class="small-muted">CC: ${esc(r.cedula)} • Móvil: ${esc(r.movil)}</div>
            </div>
          `);
        });

        document.querySelectorAll('.result-item').forEach(item => {
          item.addEventListener('click', function(){
            const cc = item.getAttribute('data-cc');
            cerrarModal();
            fetchPendientesPorCedula(cc);
          });
        });
      })
      .catch(() => {
        resultados.innerHTML = `<div class="text-danger">Error buscando</div>`;
      });
    }, 250);
  });

});
</script>
@endsection