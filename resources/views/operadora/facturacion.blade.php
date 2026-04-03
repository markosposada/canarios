@extends('layouts.app')
@section('title','Facturación')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">FACTURACIÓN OPERADORA</h3>
    <p class="text-muted mb-0">Selecciona un conductor y factura servicios pendientes + sanciones activas (sin repetir facturados).</p>
  </div>
</div>

<style>
  .tabla-fixed { table-layout: fixed; width: 100%; }
  .td-ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .col-fecha { width: 110px; }
  .col-hora  { width: 90px; }
  .col-valor { width: 110px; text-align: right; }
  .col-tipo  { width: 220px; }
  .table-responsive{ -webkit-overflow-scrolling: touch; }
  .kpi-box { border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }

  .box-fechas{
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 10px;
    padding: 10px;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
  }

    .box-fechas .form-check{
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }

  .box-fechas .form-check-input{
    position: static !important;
    margin: 0 !important;
    width: 18px;
    height: 18px;
    min-width: 18px;
    accent-color: #0d6efd;
    cursor: pointer;
  }

  .box-fechas .form-check-label{
    margin: 0;
    cursor: pointer;
    font-size: 16px;
  }

  /* modal */
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
  .modal-head{ padding: 14px 16px; border-bottom:1px solid rgba(0,0,0,.08); display:flex; align-items:center; justify-content:space-between; }
  .modal-body{ padding: 14px 16px; }
  .result-item{
    padding: 10px 12px; border:1px solid rgba(0,0,0,.08); border-radius: 12px;
    margin-bottom: 10px; cursor:pointer;
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
          <i class="mdi mdi-account-search mr-1"></i> Buscar conductor o móvil
        </button>

        <div id="msg" class="mt-3"></div>

        <hr>

        <div id="infoMovil" style="display:none;">
          <p class="mb-1"><strong>Móvil:</strong> <span id="infoMo"></span></p>
          <p class="mb-1"><strong>Conductor:</strong> <span id="infoConductor"></span></p>
          <p class="mb-1"><strong>Cédula:</strong> <span id="infoCc"></span></p>
          <p class="mb-1"><strong>Valor servicio:</strong> $<span id="infoValorServicio"></span></p>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Días pendientes a facturar</h6>
            <button class="btn btn-sm btn-light" type="button" id="btnMarcarTodos" disabled>Marcar todos</button>
          </div>

          <div id="contenedorFechas" class="box-fechas">
            <div class="text-muted">Seleccione un conductor primero</div>
          </div>

          <button class="btn btn-info w-100 mt-3" id="btnAplicarFechas" type="button" disabled>
            VER PENDIENTES SELECCIONADOS
          </button>
        </div>

        <button class="btn btn-success w-100 mt-3" id="btnFacturar" type="button" disabled>
          FACTURAR DÍAS SELECCIONADOS
        </button>
      </div>
    </div>
  </div>

  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Pendientes</h4>

        <div class="row">
          <div class="col-md-7">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h5 class="mb-0">Servicios pendientes</h5>
              <small class="text-muted">Cada servicio: $<span id="kValorServ">0</span></small>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-striped tabla-fixed" id="tablaServicios">
                <thead>
                  <tr>
                    <th>Dirección</th>
                    <th class="col-fecha">Fecha</th>
                    <th class="col-hora">Hora</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="3" class="text-muted text-center">Selecciona un conductor</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-md-5">
            <h5 class="mb-2">Sanciones activas pendientes</h5>

            <div class="table-responsive">
              <table class="table table-sm table-striped tabla-fixed" id="tablaSanciones">
                <thead>
                  <tr>
                    <th class="col-fecha">Fecha</th>
                    <th class="col-hora">Hora</th>
                    <th class="col-tipo">Tipo</th>
                    <th class="col-valor">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="4" class="text-muted text-center">Selecciona un conductor</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-4">
            <div class="p-3 kpi-box mb-2">
              <strong>Cant. servicios</strong><br>
              <span id="tCantServ">0</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 kpi-box mb-2">
              <strong>Total servicios</strong><br>
              $<span id="tServ">0</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 kpi-box mb-2">
              <strong>Total sanciones</strong><br>
              $<span id="tSanc">0</span>
            </div>
          </div>
          <div class="col-12">
            <div class="alert alert-info py-2 mb-0">
              <strong>TOTAL A COBRAR:</strong> $<span id="tTotal">0</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- MODAL --}}
<div id="modalBack" class="modal-backdrop-custom" role="dialog" aria-modal="true">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <strong>Buscar conductor</strong><br>
        <span class="small-muted">Escribe nombre, cédula o número de móvil</span>
      </div>
      <button class="btn btn-sm btn-light" id="btnCerrarModal" type="button">✕</button>
    </div>
    <div class="modal-body">
      <input type="text" class="form-control" id="buscadorConductor" placeholder="Ej: 1">
      <div class="small-muted mt-2">Resultados:</div>
      <div id="resultados" class="mt-2"></div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const urlPendCc    = "{{ route('operadora.facturacion.pendientes_cc') }}";
  const urlBuscarCon = "{{ route('operadora.facturacion.buscar_conductores') }}";
  const urlFacturar  = "{{ route('operadora.facturacion.facturar') }}";

  const btnFacturar = document.getElementById('btnFacturar');
  const btnAplicarFechas = document.getElementById('btnAplicarFechas');
  const btnMarcarTodos = document.getElementById('btnMarcarTodos');
  const msg = document.getElementById('msg');

  const infoMovil = document.getElementById('infoMovil');
  const infoMo = document.getElementById('infoMo');
  const infoConductor = document.getElementById('infoConductor');
  const infoCc = document.getElementById('infoCc');
  const infoValorServicio = document.getElementById('infoValorServicio');
  const kValorServ = document.getElementById('kValorServ');

  const contenedorFechas = document.getElementById('contenedorFechas');

  const tBodyServ = document.querySelector('#tablaServicios tbody');
  const tBodySanc = document.querySelector('#tablaSanciones tbody');

  const tCantServ = document.getElementById('tCantServ');
  const tServ = document.getElementById('tServ');
  const tSanc = document.getElementById('tSanc');
  const tTotal = document.getElementById('tTotal');

  const modalBack = document.getElementById('modalBack');
  const btnModalConductor = document.getElementById('btnModalConductor');
  const btnCerrarModal = document.getElementById('btnCerrarModal');
  const buscadorConductor = document.getElementById('buscadorConductor');
  const resultados = document.getElementById('resultados');

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

  function limitarTexto(str, max = 30){
    if (!str) return '';
    str = String(str);
    return str.length > max ? str.slice(0, max) + '…' : str;
  }

  function showMsg(html, cls='alert alert-info'){
    msg.innerHTML = `<div class="${cls} py-2">${html}</div>`;
    setTimeout(()=>msg.innerHTML='', 4000);
  }

  function limpiarTotalesYTablas(texto = 'Selecciona días pendientes'){
    tBodyServ.innerHTML = `<tr><td colspan="3" class="text-muted text-center">${texto}</td></tr>`;
    tBodySanc.innerHTML = `<tr><td colspan="4" class="text-muted text-center">${texto}</td></tr>`;
    tCantServ.textContent = '0';
    tServ.textContent = '0';
    tSanc.textContent = '0';
    tTotal.textContent = '0';
    btnFacturar.disabled = true;
  }

  function obtenerFechasSeleccionadas(){
    return Array.from(document.querySelectorAll('.check-fecha:checked')).map(el => el.value);
  }

  function actualizarBotonesChecklist(){
    const totalChecks = document.querySelectorAll('.check-fecha').length;
    const seleccionadas = obtenerFechasSeleccionadas();

    btnAplicarFechas.disabled = totalChecks === 0;
    btnMarcarTodos.disabled = totalChecks === 0;

    if (totalChecks > 0 && seleccionadas.length === totalChecks) {
      btnMarcarTodos.textContent = 'Desmarcar todos';
    } else {
      btnMarcarTodos.textContent = 'Marcar todos';
    }

    btnFacturar.disabled = true;
  }

  function llenarChecklistFechas(fechas = []){
    contenedorFechas.innerHTML = '';

    if (!fechas.length) {
      contenedorFechas.innerHTML = `<div class="text-muted">No hay días pendientes</div>`;
      btnAplicarFechas.disabled = true;
      btnMarcarTodos.disabled = true;
      btnFacturar.disabled = true;
      limpiarTotalesYTablas('No hay pendientes');
      return;
    }

    fechas.forEach(f => {
      const safeId = 'fecha_' + String(f).replaceAll('-', '_');
            contenedorFechas.insertAdjacentHTML('beforeend', `
        <div class="form-check mb-2">
          <input class="form-check-input check-fecha" type="checkbox" value="${esc(f)}" id="${esc(safeId)}">
          <label class="form-check-label" for="${esc(safeId)}">${esc(f)}</label>
        </div>
      `);
    });

    document.querySelectorAll('.check-fecha').forEach(chk => {
      chk.addEventListener('change', actualizarBotonesChecklist);
    });

    actualizarBotonesChecklist();
    limpiarTotalesYTablas();
  }

  function renderInfo(data){
    infoMovil.style.display = 'block';
    infoMo.textContent = data.movil.mo_taxi;
    infoConductor.textContent = data.movil.conductor_nombre;
    infoCc.textContent = data.movil.conductor_cc;
    infoValorServicio.textContent = data.valor_servicio;
    kValorServ.textContent = data.valor_servicio;
    currentCedula = String(data.movil.conductor_cc || '');
  }

  function renderPendientes(data){
    tBodyServ.innerHTML = '';
    if (!data.servicios.length){
      tBodyServ.innerHTML = `<tr><td colspan="3" class="text-muted text-center">No hay servicios pendientes</td></tr>`;
    } else {
      data.servicios.forEach(s => {
        const dirFull = esc(s.dis_dire || '');
        const dirCorta = limitarTexto(dirFull, 30);
        tBodyServ.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="td-ellipsis" title="${dirFull}">${dirCorta}</td>
            <td>${esc(s.dis_fecha)}</td>
            <td>${esc(s.dis_hora)}</td>
          </tr>
        `);
      });
    }

    tBodySanc.innerHTML = '';
    if (!data.sanciones.length){
      tBodySanc.innerHTML = `<tr><td colspan="4" class="text-muted text-center">No hay sanciones activas pendientes</td></tr>`;
    } else {
      data.sanciones.forEach(s => {
        const tipoFull = esc(s.tipo || '');
        const tipoCorto = limitarTexto(tipoFull, 30);
        tBodySanc.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${esc(s.sancion_fecha)}</td>
            <td>${esc(s.sancion_hora)}</td>
            <td class="td-ellipsis" title="${tipoFull}">${tipoCorto}</td>
            <td class="col-valor">$${esc(s.valor)}</td>
          </tr>
        `);
      });
    }

    tCantServ.textContent = data.totales.cantidad_servicios;
    tServ.textContent = data.totales.total_servicios;
    tSanc.textContent = data.totales.total_sanciones;
    tTotal.textContent = data.totales.total;

    const hayPendientes = (data.servicios.length + data.sanciones.length) > 0;
    btnFacturar.disabled = !hayPendientes;
  }

  function construirUrlPendientes(cedula, fechas = []){
    let url = urlPendCc + '?cedula=' + encodeURIComponent(cedula);
    fechas.forEach(f => {
      url += '&fechas[]=' + encodeURIComponent(f);
    });
    return url;
  }

  function cargarFechasPendientes(cedula){
    limpiarTotalesYTablas('Cargando...');
    fetch(construirUrlPendientes(cedula), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'Error consultando.');
      renderInfo(j);
      llenarChecklistFechas(j.fechas_pendientes || []);
    })
    .catch(e => {
      infoMovil.style.display = 'none';
      btnFacturar.disabled = true;
      btnAplicarFechas.disabled = true;
      btnMarcarTodos.disabled = true;
      contenedorFechas.innerHTML = `<div class="text-danger">Error cargando días pendientes</div>`;
      limpiarTotalesYTablas('Sin datos');
      showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    });
  }

  function cargarPendientesSeleccionados(){
    const fechas = obtenerFechasSeleccionadas();

    if(!currentCedula){
      showMsg('⚠️ Primero selecciona un conductor.', 'alert alert-warning');
      return;
    }

    if(!fechas.length){
      showMsg('⚠️ Selecciona al menos un día pendiente.', 'alert alert-warning');
      return;
    }

    tBodyServ.innerHTML = `<tr><td colspan="3" class="text-muted text-center">Cargando...</td></tr>`;
    tBodySanc.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Cargando...</td></tr>`;
    btnFacturar.disabled = true;

    fetch(construirUrlPendientes(currentCedula, fechas), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'Error consultando.');
      renderInfo(j);
      renderPendientes(j);
    })
    .catch(e => {
      limpiarTotalesYTablas('Error al cargar');
      showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    });
  }

  function abrirModal(){
    modalBack.style.display = 'flex';
    resultados.innerHTML = '';
    buscadorConductor.value = '';
    setTimeout(()=> buscadorConductor.focus(), 50);
  }

  function cerrarModal(){
    modalBack.style.display = 'none';
  }

  btnModalConductor.addEventListener('click', abrirModal);
  btnCerrarModal.addEventListener('click', cerrarModal);
  modalBack.addEventListener('click', (e)=>{ if(e.target === modalBack) cerrarModal(); });

  btnAplicarFechas.addEventListener('click', cargarPendientesSeleccionados);

  btnMarcarTodos.addEventListener('click', function(){
    const checks = Array.from(document.querySelectorAll('.check-fecha'));
    if (!checks.length) return;

    const todasMarcadas = checks.every(chk => chk.checked);
    checks.forEach(chk => chk.checked = !todasMarcadas);

    actualizarBotonesChecklist();
  });

  let tmr = null;
  buscadorConductor.addEventListener('input', function(){
    clearTimeout(tmr);
    const q = buscadorConductor.value.trim();

    resultados.innerHTML = `<div class="small-muted">Buscando móvil...</div>`;

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

            contenedorFechas.innerHTML = `<div class="text-muted">Cargando días pendientes...</div>`;
            btnAplicarFechas.disabled = true;
            btnMarcarTodos.disabled = true;
            btnFacturar.disabled = true;

            cerrarModal();
            cargarFechasPendientes(cc);
          });
        });
      })
      .catch(()=> resultados.innerHTML = `<div class="text-danger">Error buscando</div>`);
    }, 250);
  });

  btnFacturar.addEventListener('click', function(){
    if(!currentCedula) return;

    const fechas = obtenerFechasSeleccionadas();

    if(!fechas.length){
      showMsg('⚠️ Selecciona al menos un día pendiente.', 'alert alert-warning');
      return;
    }

    if(!confirm('¿Confirmas facturar lo pendiente de los días seleccionados?')) return;

    btnFacturar.disabled = true;

    fetch(urlFacturar, {
      method:'POST',
      headers:{
        'X-Requested-With':'XMLHttpRequest',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        cedula: currentCedula,
        fechas: fechas
      })
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'No se pudo facturar.');
      showMsg('✅ Facturado correctamente. Comprobante #' + j.fo_id + ' | Total $' + j.total, 'alert alert-success');
      cargarFechasPendientes(currentCedula);
    })
    .catch(e => showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(() => btnFacturar.disabled = false);
  });

});
</script>
@endsection