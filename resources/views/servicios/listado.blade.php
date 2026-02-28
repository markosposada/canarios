@extends('layouts.app')

@section('content')
<style>
  .page-wrap{ max-width: 1100px; }
  .page-title{ font-weight: 800; letter-spacing: .2px; }

  .svc-card{
    border: 0;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
  }
  .svc-kv{
    display:flex; align-items:flex-start; justify-content:space-between; gap:10px;
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,.08);
  }
  .svc-kv:last-child{ border-bottom: 0; }
  .svc-k{ font-size: 12px; color: #6c757d; flex: 0 0 auto; }
  .svc-v{ font-size: 14px; font-weight: 600; text-align: right; flex: 1 1 auto; word-break: break-word; }
  .svc-v.text-left{ text-align:left; font-weight: 500; }

  .table thead th{ white-space: nowrap; }
  .table td{ vertical-align: middle; }
  .token-pill{
    display:inline-block; padding: 2px 8px; border-radius: 999px;
    background: rgba(0,0,0,.06); font-weight: 800; letter-spacing: .5px;
  }

  @media (max-width: 576px){
    .card-body{ padding: 12px; }
    .btn-action{ padding: .55rem .9rem; }
  }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0 page-title">Servicios registrados</h2>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-3">
          <label class="form-label mb-1">Desde</label>
          <input type="date" id="fDesde" class="form-control">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label mb-1">Hasta</label>
          <input type="date" id="fHasta" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label mb-1">Buscar</label>
          <input type="text" id="fQuery" class="form-control" placeholder="Usuario, dirección, token, móvil, placa, conductor...">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button id="btnBuscar" class="btn btn-dark w-100 btn-action">Filtrar</button>
        </div>
      </div>
      <small class="text-muted d-block mt-2">
        Tip: en celular verás tarjetas; en computador verás tabla completa.
      </small>
    </div>
  </div>

  <div id="listaCards" class="d-lg-none"></div>

  <div class="table-responsive d-none d-lg-block">
    <table class="table table-hover align-middle text-center" id="tablaServicios">
      <thead class="table-dark">
        <tr>
          <th>Fecha</th>
          <th>Hora</th>
          <th class="text-start">Dirección</th>
          <th class="text-start">Usuario</th>
          <th>Token</th>
          <th>Móvil</th>
          <th>Placa</th>
          <th class="text-start">Conductor</th>
          <th>Estado</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

function badgeEstado(estado) {
  if (Number(estado) === 2) return '<span class="badge bg-danger">Cancelado</span>';
  return '<span class="badge bg-success">Activo</span>';
}

/**
 * ✅ Acción:
 * - si está cancelado => botón ACTIVAR
 * - si está activo y dentro de 1 hora => botón CANCELAR
 * - si está activo pero venció => No disponible
 */
function botonAccion(id, estado, puede_cancelar) {
  if (Number(estado) === 2) {
    return `<button class="btn btn-sm btn-success" onclick="activar(${id})">Activar</button>`;
  }
  if (Number(puede_cancelar) === 0) {
    return '<button class="btn btn-sm btn-outline-secondary" disabled title="Tiempo de cancelación vencido">No disponible</button>';
  }
  return `<button class="btn btn-sm btn-danger" onclick="cancelar(${id})">Cancelar</button>`;
}

function esc(v){ return (v ?? '').toString(); }

function pintarTabla(rows) {
  const $tb = $('#tablaServicios tbody').empty();

  if (!rows.length) {
    $tb.append(`<tr><td colspan="10" class="text-muted">No hay registros con los filtros actuales.</td></tr>`);
    return;
  }

  rows.forEach(r => {
    $tb.append(`
      <tr>
        <td>${esc(r.fecha)}</td>
        <td>${esc(r.hora)}</td>
        <td class="text-start">${esc(r.direccion)}</td>
        <td class="text-start">${esc(r.usuario)}</td>
        <td><span class="token-pill">${esc(r.token)}</span></td>
        <td>${esc(r.movil)}</td>
        <td>${esc(r.placa)}</td>
        <td class="text-start">${esc(r.conductor)}</td>
        <td>${badgeEstado(r.estado)}</td>
        <td>${botonAccion(r.id, r.estado, r.puede_cancelar)}</td>
      </tr>
    `);
  });
}

function pintarCards(rows){
  const $wrap = $('#listaCards').empty();

  if (!rows.length) {
    $wrap.append(`
      <div class="card svc-card">
        <div class="card-body text-center text-muted">
          No hay registros con los filtros actuales.
        </div>
      </div>
    `);
    return;
  }

  rows.forEach(r => {
    const estadoHtml = badgeEstado(r.estado);
    const accionHtml = botonAccion(r.id, r.estado, r.puede_cancelar);

    $wrap.append(`
      <div class="card svc-card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-bold">${esc(r.fecha)} <span class="text-muted fw-normal">•</span> ${esc(r.hora)}</div>
              <div class="text-muted" style="font-size:12px">Token: <span class="token-pill">${esc(r.token)}</span></div>
            </div>
            <div class="text-end">
              ${estadoHtml}
              <div class="mt-2">${accionHtml}</div>
            </div>
          </div>

          <div class="mt-3">
            <div class="svc-kv">
              <div class="svc-k">Dirección</div>
              <div class="svc-v text-left">${esc(r.direccion)}</div>
            </div>
            <div class="svc-kv">
              <div class="svc-k">Usuario</div>
              <div class="svc-v text-left">${esc(r.usuario)}</div>
            </div>
            <div class="svc-kv">
              <div class="svc-k">Móvil</div>
              <div class="svc-v">${esc(r.movil)}</div>
            </div>
            <div class="svc-kv">
              <div class="svc-k">Placa</div>
              <div class="svc-v">${esc(r.placa)}</div>
            </div>
            <div class="svc-kv">
              <div class="svc-k">Conductor</div>
              <div class="svc-v text-left">${esc(r.conductor)}</div>
            </div>
          </div>
        </div>
      </div>
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

  $('#tablaServicios tbody').html('<tr><td colspan="10" class="text-muted">Cargando...</td></tr>');
  $('#listaCards').html(`
    <div class="card svc-card">
      <div class="card-body text-muted">Cargando...</div>
    </div>
  `);

  try {
    const res = await fetch(`{{ route('servicios.listar') }}?${params.toString()}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const raw = await res.text();
      console.error('Respuesta NO JSON:', raw.slice(0, 400));
      throw new Error('El servidor no devolvió JSON (posible redirección/login).');
    }

    const data = await res.json();
    const rows = Array.isArray(data) ? data : [];

    pintarTabla(rows);
    pintarCards(rows);
  } catch (e) {
    console.error(e);
    $('#tablaServicios tbody').html('<tr><td colspan="10" class="text-danger">Error al cargar los datos.</td></tr>');
    $('#listaCards').html(`
      <div class="card svc-card">
        <div class="card-body text-danger">
          Error al cargar los datos. Revisa sesión o el endpoint.
        </div>
      </div>
    `);
  }
}

async function cancelar(id) {
  const ok = await Swal.fire({
    icon: 'warning',
    title: 'Cancelar servicio',
    text: '¿Seguro que deseas cancelar este servicio?',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No',
  }).then(r => r.isConfirmed);

  if (!ok) return;

  try {
    const res = await fetch(`{{ url('/servicios/cancelar') }}/${id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const raw = await res.text();
      console.error('Respuesta NO JSON:', raw.slice(0, 400));
      throw new Error('El servidor no devolvió JSON.');
    }

    const data = await res.json();

    if (data.success) {
      Swal.fire('Listo', 'Servicio cancelado.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', 'No fue posible cancelar (¿ya estaba cancelado o vencido?).', 'info');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudo cancelar el servicio.', 'error');
  }
}

/** ✅ NUEVO: activar servicio cancelado */
async function activar(id) {
  const ok = await Swal.fire({
    icon: 'question',
    title: 'Activar servicio',
    text: '¿Deseas reactivar este servicio?',
    showCancelButton: true,
    confirmButtonText: 'Sí, activar',
    cancelButtonText: 'No',
  }).then(r => r.isConfirmed);

  if (!ok) return;

  try {
    const res = await fetch(`{{ url('/servicios/activar') }}/${id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const raw = await res.text();
      console.error('Respuesta NO JSON:', raw.slice(0, 400));
      throw new Error('El servidor no devolvió JSON.');
    }

    const data = await res.json();

    if (data.success) {
      Swal.fire('Listo', 'Servicio activado de nuevo.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', 'No fue posible activar (¿ya estaba activo?).', 'info');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudo activar el servicio.', 'error');
  }
}

$('#btnBuscar').on('click', cargarTabla);
$('#fQuery').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); cargarTabla(); } });

/** ✅ Fecha de hoy en hora LOCAL (evita el +1 día por UTC) */
function hoyLocalYYYYMMDD() {
  const d = new Date();
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

document.addEventListener('DOMContentLoaded', () => {
  const hoy = hoyLocalYYYYMMDD();
  $('#fDesde').val(hoy);
  $('#fHasta').val(hoy);
  cargarTabla();
});
</script>
@endsection
