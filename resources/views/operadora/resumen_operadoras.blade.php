@extends('layouts.app')

@section('title', 'Resumen por operadora')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">RESUMEN DE CARRERAS POR OPERADORA</h3>
    <p class="text-muted mb-0">Consulta carreras asignadas agrupadas por operadora, con filtros por fecha y operadora.</p>
  </div>
</div>

<style>
  .kpi-box{
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,.08);
    background: #fff;
  }
</style>

<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-body">

        <div class="row g-2 align-items-end mb-3">
          <div class="col-md-3">
            <label class="form-label mb-1">Desde</label>
            <input type="date" id="fDesde" class="form-control">
          </div>

          <div class="col-md-3">
            <label class="form-label mb-1">Hasta</label>
            <input type="date" id="fHasta" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label mb-1">Operadora</label>
            <select id="fOperadora" class="form-control" {{ !$esAdmin ? 'disabled' : '' }}>
  <option value="">Todas</option>
</select>
          </div>

          <div class="col-md-2">
            <button class="btn btn-dark w-100" id="btnFiltrar" type="button">Filtrar</button>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <div class="p-3 kpi-box">
              <strong>Total carreras</strong><br>
              <span id="kpiTotal">0</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 kpi-box">
              <strong>Activas</strong><br>
              <span id="kpiActivas">0</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 kpi-box">
              <strong>Canceladas</strong><br>
              <span id="kpiCanceladas">0</span>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle text-center" id="tablaResumen">
            <thead class="table-dark">
              <tr>
                <th class="text-start">Operadora</th>
                <th>Total carreras</th>
                <th>Activas</th>
                <th>Canceladas</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="4" class="text-muted text-center">Cargando...</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const urlResumen = "{{ route('operadora.resumen_operadoras.listar') }}";
  const urlOperadoras = "{{ route('operadora.resumen_operadoras.operadoras') }}";

  const fDesde = document.getElementById('fDesde');
  const fHasta = document.getElementById('fHasta');
  const fOperadora = document.getElementById('fOperadora');
  const btnFiltrar = document.getElementById('btnFiltrar');

  const tbody = document.querySelector('#tablaResumen tbody');

  const kpiTotal = document.getElementById('kpiTotal');
  const kpiActivas = document.getElementById('kpiActivas');
  const kpiCanceladas = document.getElementById('kpiCanceladas');

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function hoyLocalYYYYMMDD() {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }

async function cargarOperadoras() {
  try {
    const res = await fetch(urlOperadoras, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const j = await res.json();

    fOperadora.innerHTML = '';

    @if($esAdmin)
      fOperadora.insertAdjacentHTML('beforeend', '<option value="">Todas</option>');
    @endif

    (j.data || []).forEach(op => {
      fOperadora.insertAdjacentHTML(
        'beforeend',
        `<option value="${esc(op)}">${esc(op)}</option>`
      );
    });

    @if(!$esAdmin)
      if ((j.data || []).length > 0) {
        fOperadora.value = j.data[0];
      }
    @endif

  } catch (e) {
    console.error(e);
  }
}

  async function cargarResumen() {
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Cargando...</td></tr>`;

    const params = new URLSearchParams();
    if (fDesde.value) params.append('desde', fDesde.value);
    if (fHasta.value) params.append('hasta', fHasta.value);
    if (fOperadora.value) params.append('operadora', fOperadora.value);

    try {
      const res = await fetch(urlResumen + '?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      const j = await res.json();

      if (!j.ok) {
        throw new Error('No se pudo cargar el resumen.');
      }

      const rows = j.data || [];
      const tot = j.totales || {};

      kpiTotal.textContent = tot.total_carreras || 0;
      kpiActivas.textContent = tot.activas || 0;
      kpiCanceladas.textContent = tot.canceladas || 0;

      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">No hay resultados.</td></tr>`;
        return;
      }

      tbody.innerHTML = '';

      rows.forEach(r => {
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="text-start">${esc(r.operadora)}</td>
            <td>${esc(r.total_carreras)}</td>
            <td>${esc(r.activas)}</td>
            <td>${esc(r.canceladas)}</td>
          </tr>
        `);
      });

    } catch (e) {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error cargando datos.</td></tr>`;
    }
  }

  btnFiltrar.addEventListener('click', cargarResumen);

  [fDesde, fHasta, fOperadora].forEach(el => {
    el.addEventListener('change', cargarResumen);
  });

  const hoy = hoyLocalYYYYMMDD();
  fDesde.value = hoy;
  fHasta.value = hoy;

  cargarOperadoras();
  cargarResumen();
});
</script>
@endsection