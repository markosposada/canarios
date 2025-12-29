@extends('layouts.app')
@section('title','Facturas pendientes')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">FACTURAS PENDIENTES POR PAGAR</h3>
    <p class="text-muted mb-0">Aquí ves todo lo facturado pendiente, sin importar la operadora que facturó.</p>
  </div>
</div>

<style>
  .kpi-box { border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }
  .tabla-fixed { table-layout: fixed; width: 100%; }
  .td-ellipsis { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .col-fecha { width: 110px; }
  .col-hora  { width: 85px; }
  .col-movil { width: 80px; }
  .col-total { width: 120px; text-align:right; font-weight: 800; }
  .col-cc    { width: 120px; }
  .col-fact  { width: 90px; }
</style>

<div class="row">
  <div class="col-lg-4 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Buscar</h4>

        <label class="form-label fw-bold">Filtro</label>
        <input type="text" id="q" class="form-control" placeholder="Factura, móvil, cédula o nombre">

        <button class="btn btn-primary w-100 mt-3" id="btnBuscar" type="button">
          Buscar
        </button>

        <div id="msg" class="mt-3"></div>

        <hr>

        <div class="p-3 kpi-box mb-2">
          <strong>Cantidad pendientes</strong><br>
          <span id="kCantidad">0</span>
        </div>

        <div class="p-3 kpi-box mb-2">
          <strong>Total servicios</strong><br>
          $<span id="kServ">0</span>
        </div>

        <div class="p-3 kpi-box mb-2">
          <strong>Total sanciones</strong><br>
          $<span id="kSanc">0</span>
        </div>

        <div class="alert alert-info py-2 mb-0">
          <strong>TOTAL PENDIENTE:</strong> $<span id="kTotal">0</span>
        </div>

      </div>
    </div>
  </div>

  <div class="col-lg-8 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Listado</h4>

        <div class="table-responsive">
          <table class="table table-sm table-striped tabla-fixed" id="tabla">
            <thead>
              <tr>
                <th class="col-fact">Factura</th>
                <th class="col-movil">Móvil</th>
                <th class="col-cc">Cédula</th>
                <th>Conductor</th>
                <th class="col-fecha">Fecha</th>
                <th class="col-hora">Hora</th>
                <th class="col-total">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="7" class="text-muted text-center">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <small class="text-muted d-block mt-2">
          Tip: toca una fila para ver detalles (operadora, serv/sanc) en el tooltip.
        </small>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const urlListar = "{{ route('operadora.facturas_pendientes.listar') }}";

  const q = document.getElementById('q');
  const btnBuscar = document.getElementById('btnBuscar');
  const msg = document.getElementById('msg');

  const kCantidad = document.getElementById('kCantidad');
  const kServ = document.getElementById('kServ');
  const kSanc = document.getElementById('kSanc');
  const kTotal = document.getElementById('kTotal');

  const tbody = document.querySelector('#tabla tbody');

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
    setTimeout(()=>msg.innerHTML='', 3500);
  }

  function render(json){
    const rows = json.data || [];
    const t = json.totales || {};

    kCantidad.textContent = String(t.cantidad || rows.length || 0);
    kServ.textContent = String(t.total_servicios || 0);
    kSanc.textContent = String(t.total_sanciones || 0);
    kTotal.textContent = String(t.total || 0);

    tbody.innerHTML = '';

    if(rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="7" class="text-muted text-center">No hay facturas pendientes</td></tr>`;
      return;
    }

    rows.forEach(r => {
      const tooltip = `Operadora: ${esc(r.fo_operadora)} | Servicios: $${esc(r.fo_total_servicios)} | Sanciones: $${esc(r.fo_total_sanciones)}`;
      tbody.insertAdjacentHTML('beforeend', `
        <tr title="${tooltip}">
          <td>#${esc(r.fo_id)}</td>
          <td>${esc(r.fo_movil)}</td>
          <td>${esc(r.fo_conductor)}</td>
          <td class="td-ellipsis" title="${esc(r.conductor_nombre)}">${esc(r.conductor_nombre)}</td>
          <td>${esc(r.fo_fecha)}</td>
          <td>${esc(r.fo_hora)}</td>
          <td class="col-total">$${esc(r.fo_total)}</td>
        </tr>
      `);
    });
  }

  function cargar(){
    const query = q.value.trim();
    btnBuscar.disabled = true;
    tbody.innerHTML = `<tr><td colspan="7" class="text-muted text-center">Cargando...</td></tr>`;

    fetch(urlListar + (query ? ('?q=' + encodeURIComponent(query)) : ''), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if(!ok || !j.ok) throw new Error(j.message || 'Error listando.');
      render(j);
    })
    .catch(e => {
      tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">Error cargando</td></tr>`;
      showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    })
    .finally(()=> btnBuscar.disabled = false);
  }

  btnBuscar.addEventListener('click', cargar);
  q.addEventListener('keydown', (e) => { if(e.key === 'Enter') cargar(); });

  cargar(); // carga inicial
});
</script>
@endsection
