@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Servicios registrados</h2>
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
          <input type="text" id="fQuery" class="form-control" placeholder="Usuario, dirección, token, móvil, placa, conductor...">
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
          <th>Dirección</th>
          <th>Usuario</th>
          <th>Token</th>
          <th>Móvil</th>
          <th>Placa</th>
          <th>Conductor</th>
          <th>Estado</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

{{-- Libs --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

function badgeEstado(estado) {
  if (Number(estado) === 2) return '<span class="badge bg-danger">Cancelado</span>';
  return '<span class="badge bg-success">Activo</span>';
}

// AHORA RECIBE puede_cancelar
function botonAccion(id, estado, puede_cancelar) {
  // ya cancelado
  if (Number(estado) === 2) {
    return '<button class="btn btn-sm btn-outline-secondary" disabled>—</button>';
  }

  // pasó la hora límite
  if (Number(puede_cancelar) === 0) {
    return '<button class="btn btn-sm btn-outline-secondary" disabled title="Tiempo de cancelación vencido">No disponible</button>';
  }

  // se puede cancelar
  return `<button class="btn btn-sm btn-danger" onclick="cancelar(${id})">Cancelar</button>`;
}

function pintarTabla(rows) {
  const $tb = $('#tablaServicios tbody').empty();

  if (!rows.length) {
    $tb.append(`<tr><td colspan="10" class="text-muted">No hay registros con los filtros actuales.</td></tr>`);
    return;
  }

  rows.forEach(r => {
    $tb.append(`
      <tr>
        <td>${r.fecha ?? ''}</td>
        <td>${r.hora ?? ''}</td>
        <td class="text-start">${r.direccion ?? ''}</td>
        <td class="text-start">${r.usuario ?? ''}</td>
        <td><span class="fw-semibold">${r.token ?? ''}</span></td>
        <td>${r.movil ?? ''}</td>
        <td>${r.placa ?? ''}</td>
        <td class="text-start">${r.conductor ?? ''}</td>
        <td>${badgeEstado(r.estado)}</td>
        <td>${botonAccion(r.id, r.estado, r.puede_cancelar)}</td>
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

  $('#tablaServicios tbody').html('<tr><td colspan="10" class="text-muted">Cargando...</td></tr>');

  try {
    const res = await fetch(`{{ route('servicios.listar') }}?${params.toString()}`);
    const data = await res.json();
    pintarTabla(data);
  } catch {
    $('#tablaServicios tbody').html('<tr><td colspan="10" class="text-danger">Error al cargar los datos.</td></tr>');
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
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    const data = await res.json();

    if (data.success) {
      Swal.fire('Listo', 'Servicio cancelado.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', 'No fue posible cancelar (¿ya estaba cancelado o vencido?).', 'info');
    }
  } catch {
    Swal.fire('Error', 'No se pudo cancelar el servicio.', 'error');
  }
}

$('#btnBuscar').on('click', cargarTabla);
$('#fQuery').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); cargarTabla(); } });

// Fechas por defecto: hoy
document.addEventListener('DOMContentLoaded', () => {
  const hoy = new Date().toISOString().slice(0,10);
  $('#fDesde').val(hoy);
  $('#fHasta').val(hoy);
  cargarTabla();
});
</script>
@endsection
