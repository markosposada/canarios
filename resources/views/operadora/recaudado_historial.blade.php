@extends('layouts.app')
@section('title','Historial Recaudos')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">HISTORIAL DE RECAUDOS</h3>
    <p class="text-muted mb-0">Aquí ves las facturas que tú registraste como pagadas.</p>
  </div>
</div>

<style>
  .tabla-fixed{ table-layout: fixed; width: 100%; }
  .td-ellipsis{ white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

  .col-fecha{ width: 115px; }
  .col-hora { width: 85px; }
  .col-movil{ width: 80px; }
  .col-total{ width: 120px; text-align:right; font-weight:800; }
  .col-met  { width: 120px; }

  .kpi-box { border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }
  .kpi-box strong { font-size: 13px; }
  .kpi-box span { font-weight: 900; font-size: 18px; }
</style>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">

        {{-- filtros --}}
        <div class="d-flex flex-wrap align-items-end" style="gap:10px;">
          <div>
            <label class="form-label fw-bold mb-1">Desde</label>
            <input type="date" id="desde" class="form-control">
          </div>
          <div>
            <label class="form-label fw-bold mb-1">Hasta</label>
            <input type="date" id="hasta" class="form-control">
          </div>
          <div>
            <label class="form-label fw-bold mb-1">Móvil</label>
            <input type="text" id="movil" class="form-control" inputmode="numeric" placeholder="Ej: 17">
          </div>
          <div style="min-width:260px; flex:1;">
            <label class="form-label fw-bold mb-1">Buscar</label>
            <input type="text" id="q" class="form-control" placeholder="Conductor, cédula, factura...">
          </div>

          <div>
            <button class="btn btn-primary" id="btnBuscar" type="button">
              <i class="mdi mdi-magnify"></i> Buscar
            </button>
          </div>
        </div>

        {{-- KPIs --}}
        <div class="row mt-3">
          <div class="col-md-3">
            <div class="p-3 kpi-box mb-2">
              <strong>Registros</strong><br>
              <span id="kCantidad">0</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 kpi-box mb-2">
              <strong>Total servicios</strong><br>
              $<span id="kServ">0</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 kpi-box mb-2">
              <strong>Total sanciones</strong><br>
              $<span id="kSanc">0</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 kpi-box mb-2">
              <strong>Total recaudado</strong><br>
              $<span id="kTotal">0</span>
            </div>
          </div>
        </div>

        <div class="mt-2 d-flex justify-content-between align-items-center">
          <div id="msg"></div>
          <div class="text-muted">
            Total registros: <strong id="totalReg">0</strong>
          </div>
        </div>

        <hr>

        {{-- tabla --}}
        <div class="table-responsive">
          <table class="table table-sm table-striped tabla-fixed" id="tabla">
            <thead>
              <tr>
                <th>Factura</th>
                <th class="col-movil">Móvil</th>
                <th>Conductor</th>
                <th class="col-fecha">Pagó</th>
                <th class="col-met">Método</th>
                <th class="col-total">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="6" class="text-muted text-center">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <small class="text-muted d-block mt-2">
          Tip: pasa el mouse por “Conductor” para ver cédula y observación.
        </small>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const url = "{{ route('operadora.recaudado.historial.listar') }}";

  const desde = document.getElementById('desde');
  const hasta = document.getElementById('hasta');
  const movil = document.getElementById('movil');
  const q = document.getElementById('q');

  const btnBuscar = document.getElementById('btnBuscar');
  const tbody = document.querySelector('#tabla tbody');
  const totalReg = document.getElementById('totalReg');
  const msg = document.getElementById('msg');

  // KPIs
  const kCantidad = document.getElementById('kCantidad');
  const kServ = document.getElementById('kServ');
  const kSanc = document.getElementById('kSanc');
  const kTotal = document.getElementById('kTotal');

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function showMsg(text, cls='alert alert-info'){
    msg.innerHTML = `<div class="${cls} py-2 mb-0">${text}</div>`;
    setTimeout(()=> msg.innerHTML = '', 3500);
  }

  function buildUrl(){
    const p = new URLSearchParams();
    if (desde.value) p.set('desde', desde.value);
    if (hasta.value) p.set('hasta', hasta.value);
    if (movil.value) p.set('movil', movil.value);
    if (q.value) p.set('q', q.value.trim());
    return url + '?' + p.toString();
  }

  function render(j){
    const rows = j.data || [];
    const t = j.totales || {cantidad:0,total:0,total_servicios:0,total_sanciones:0};

    // KPIs
    kCantidad.textContent = String(t.cantidad || 0);
    kServ.textContent = String(t.total_servicios || 0);
    kSanc.textContent = String(t.total_sanciones || 0);
    kTotal.textContent = String(t.total || 0);

    totalReg.textContent = String(j.total || 0);
    tbody.innerHTML = '';

    if (!rows || rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">No hay recaudos en ese filtro.</td></tr>`;
      return;
    }

    rows.forEach(r => {
      const nombre = esc(r.conduc_nombres || '—');
      const cc = esc(r.fo_conductor || '');
      const obs = esc(r.fo_observacion || '');
      const title = `CC: ${cc}${obs ? ' | Obs: ' + obs : ''} | Serv: $${esc(r.fo_total_servicios)} | Sanc: $${esc(r.fo_total_sanciones)}`;

      tbody.insertAdjacentHTML('beforeend', `
        <tr title="${title}">
          <td class="td-ellipsis" title="Factura #${esc(r.fo_id)}">#${esc(r.fo_id)}</td>
          <td>${esc(r.fo_movil)}</td>
          <td class="td-ellipsis" title="${title}">${nombre}</td>
          <td>${esc((r.fo_pagado_at || '').toString().slice(0,10))}</td>
          <td>${esc(r.fo_metodo || '—')}</td>
          <td style="text-align:right; font-weight:800;">$${esc(r.fo_total)}</td>
        </tr>
      `);
    });
  }

  function cargar(){
    tbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">Cargando...</td></tr>`;
    fetch(buildUrl(), { headers: {'X-Requested-With':'XMLHttpRequest'} })
      .then(r => r.json())
      .then(j => {
        if(!j.ok) throw new Error('No se pudo cargar');
        render(j);
      })
      .catch(e => {
        tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Error cargando</td></tr>`;
        showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
      });
  }

  btnBuscar.addEventListener('click', cargar);

  // carga inicial
  cargar();
});
</script>
@endsection
