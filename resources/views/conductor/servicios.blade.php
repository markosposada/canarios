@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Mis servicios</h2>
    <div class="text-muted small">Conductor: {{ Auth::user()->name ?? '' }}</div>
  </div>

  {{-- Filtros --}}
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-3">
          <label class="form-label">Desde</label>
          <input type="date" id="fDesde" class="form-control">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Hasta</label>
          <input type="date" id="fHasta" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Buscar</label>
          <input type="text" id="fQuery" class="form-control" placeholder="Dirección, móvil o placa">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button id="btnBuscar" class="btn btn-dark w-100">Filtrar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="table-responsive">
    <table class="table table-hover align-middle text-center" id="tablaServicios">
      <thead class="table-dark">
        <tr>
          <th>Fecha</th>
          <th>Hora</th>
          <th class="text-start">Dirección</th>
          <th>Móvil</th>
          <th>Placa</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

{{-- Libs --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function pintarTabla(rows) {
  const $tb = $('#tablaServicios tbody').empty();
  if (!rows.length) {
    $tb.append('<tr><td colspan="5" class="text-muted">No hay servicios.</td></tr>');
    return;
  }
  rows.forEach(r => {
  let estadoTxt = '';
  if (r.estado == 1) estadoTxt = 'Aceptado';
  else if (r.estado == 2) estadoTxt = 'Cancelado';
  else estadoTxt = r.estado ?? '-';

  $tb.append(`
    <tr>
      <td>${r.fecha ?? ''}</td>
      <td>${r.hora ?? ''}</td>
      <td class="text-start">${r.direccion ?? ''}</td>
      <td>${r.movil ?? ''}</td>
      <td>${r.placa ?? ''}</td>
      <td>${estadoTxt}</td>
    </tr>
  `);
});

}

async function cargarTabla() {
  const params = new URLSearchParams();
  const d = $('#fDesde').val();
  const h = $('#fHasta').val();
  const q = $('#fQuery').val().trim();

  if (d) params.append('desde', d);
  if (h) params.append('hasta', h);
  if (q) params.append('q', q);

  $('#tablaServicios tbody').html('<tr><td colspan="5" class="text-muted">Cargando...</td></tr>');

  try {
    const res = await fetch(`{{ route('conductor.servicios.listar') }}?${params.toString()}`);
    const data = await res.json();
    pintarTabla(data);
  } catch {
    $('#tablaServicios tbody').html('<tr><td colspan="5" class="text-danger">Error al cargar.</td></tr>');
  }
}

$('#btnBuscar').on('click', cargarTabla);
$('#fQuery').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); cargarTabla(); } });

document.addEventListener('DOMContentLoaded', () => {
  const hoy = new Date().toISOString().slice(0,10);
  $('#fDesde').val(hoy);
  $('#fHasta').val(hoy);
  cargarTabla();
});
</script>
@endsection
