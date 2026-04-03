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
          <th class="text-start">Operadora</th>
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

<!-- Modal Modificar Servicio -->
<div class="modal fade" id="modalModificarServicio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Modificar servicio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modServicioId">
        <input type="hidden" id="modEstadoActual">

        <div class="mb-3">
          <label class="form-label fw-bold">¿Qué deseas hacer?</label>
          <select id="modAccion" class="form-select">
            <option value="">Seleccione una opción</option>
            <option value="editar_datos">Modificar dirección / usuario</option>
            <option value="cancelar">Cancelar servicio</option>
            <option value="cambiar_movil">Cambiar móvil</option>
          </select>
        </div>

        <div id="bloqueEditarDatos" style="display:none;">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Dirección</label>
              <input type="text" id="modDireccion" class="form-control" maxlength="255" placeholder="Ingrese la dirección">
            </div>
            <div class="col-12">
              <label class="form-label">Usuario</label>
              <input type="text" id="modUsuario" class="form-control" maxlength="200" placeholder="Ingrese el nombre del usuario">
            </div>
          </div>
        </div>

        <div id="bloqueCancelar" style="display:none;">
          <div class="alert alert-warning mb-0">
            Esta acción cancelará el servicio actual.
          </div>
        </div>

        <div id="bloqueMoviles" style="display:none;">
          <div class="row g-2 mb-3">
            <div class="col-md-7">
              <label class="form-label">Buscar móvil</label>
              <input type="text" id="buscarMovilModal" class="form-control" placeholder="Escribe número del móvil">
            </div>
            <div class="col-md-5 d-flex align-items-end">
              <button type="button" class="btn btn-dark w-100" id="btnBuscarMovilModal">Buscar</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle text-center">
              <thead class="table-dark">
                <tr>
                  <th>Móvil</th>
                  <th>Placa</th>
                  <th class="text-start">Conductor</th>
                  <th>Servicios</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody id="tablaMovilesModal">
                <tr>
                  <td colspan="5" class="text-muted">Seleccione "Cambiar móvil" para cargar opciones.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btnCerrarModalModificar">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarDatosServicio" style="display:none;">
          Guardar cambios
        </button>
        <button type="button" class="btn btn-danger" id="btnConfirmarCancelar" style="display:none;">
          Confirmar cancelación
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

let modalModificarServicio = null;

function esc(v){
  return (v ?? '').toString();
}

function escAttr(v){
  return esc(v)
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
}

function badgeEstado(estado) {
  if (Number(estado) === 2) return '<span class="badge bg-danger">Cancelado</span>';
  return '<span class="badge bg-success">Activo</span>';
}

$('#btnCerrarModalModificar').on('click', function () {
  if (modalModificarServicio) {
    modalModificarServicio.hide();
  }
});

function botonAccion(id, estado, puede_cancelar, direccion, usuario) {
  if (Number(puede_cancelar) === 0) {
    return '<button class="btn btn-sm btn-outline-secondary" disabled title="Tiempo de modificación vencido">No disponible</button>';
  }

  return `
    <button
      type="button"
      class="btn btn-sm btn-primary btn-modificar-servicio"
      data-id="${escAttr(id)}"
      data-estado="${escAttr(estado)}"
      data-direccion="${encodeURIComponent(direccion ?? '')}"
      data-usuario="${encodeURIComponent(usuario ?? '')}">
      Modificar
    </button>
  `;
}

function pintarTabla(rows) {
  const $tb = $('#tablaServicios tbody').empty();

  if (!rows.length) {
    $tb.append(`<tr><td colspan="11" class="text-muted">No hay registros con los filtros actuales.</td></tr>`);
    return;
  }

  rows.forEach(r => {
    $tb.append(`
      <tr>
        <td>${esc(r.fecha)}</td>
        <td>${esc(r.hora)}</td>
        <td class="text-start">${esc(r.direccion)}</td>
        <td class="text-start">${esc(r.usuario)}</td>
        <td class="text-start">${esc(r.operadora).substring(0, 8)}</td>
        <td><span class="token-pill">${esc(r.token)}</span></td>
        <td>${esc(r.movil)}</td>
        <td>${esc(r.placa)}</td>
        <td class="text-start">
          ${esc(r.conductor).length > 10
            ? esc(r.conductor).substring(0, 10) + '...'
            : esc(r.conductor)}
        </td>
        <td>${badgeEstado(r.estado)}</td>
        <td>${botonAccion(r.id, r.estado, r.puede_cancelar, r.direccion, r.usuario)}</td>
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
    const accionHtml = botonAccion(r.id, r.estado, r.puede_cancelar, r.direccion, r.usuario);

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
              <div class="svc-k">Operadora</div>
              <div class="svc-v text-left">
                ${esc(r.operadora).length > 8
                  ? esc(r.operadora).substring(0, 8) + '...'
                  : esc(r.operadora)}
              </div>
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
              <div class="svc-v text-left">
                ${esc(r.conductor).length > 10
                  ? esc(r.conductor).substring(0, 10) + '...'
                  : esc(r.conductor)}
              </div>
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

  $('#tablaServicios tbody').html('<tr><td colspan="11" class="text-muted">Cargando...</td></tr>');
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
    $('#tablaServicios tbody').html('<tr><td colspan="11" class="text-danger">Error al cargar los datos.</td></tr>');
    $('#listaCards').html(`
      <div class="card svc-card">
        <div class="card-body text-danger">
          Error al cargar los datos. Revisa sesión o el endpoint.
        </div>
      </div>
    `);
  }
}

function abrirModificar(id, estado, direccion, usuario) {
  $('#modServicioId').val(id);
  $('#modEstadoActual').val(estado);

  $('#modAccion').val('');
  $('#modDireccion').val(direccion || '');
  $('#modUsuario').val(usuario || '');

  $('#bloqueMoviles').hide();
  $('#bloqueCancelar').hide();
  $('#bloqueEditarDatos').hide();

  $('#btnConfirmarCancelar').hide();
  $('#btnGuardarDatosServicio').hide();

  $('#buscarMovilModal').val('');
  $('#tablaMovilesModal').html(`
    <tr>
      <td colspan="5" class="text-muted">Seleccione "Cambiar móvil" para cargar opciones.</td>
    </tr>
  `);

  if (!modalModificarServicio) {
    modalModificarServicio = new bootstrap.Modal(document.getElementById('modalModificarServicio'));
  }

  modalModificarServicio.show();
}

$(document).on('click', '.btn-modificar-servicio', function () {
  const id = $(this).data('id');
  const estado = $(this).data('estado');
  const direccion = decodeURIComponent($(this).attr('data-direccion') || '');
  const usuario = decodeURIComponent($(this).attr('data-usuario') || '');

  abrirModificar(id, estado, direccion, usuario);
});

$('#modAccion').on('change', function () {
  const accion = $(this).val();

  $('#bloqueMoviles').hide();
  $('#bloqueCancelar').hide();
  $('#bloqueEditarDatos').hide();

  $('#btnConfirmarCancelar').hide();
  $('#btnGuardarDatosServicio').hide();

  if (accion === 'editar_datos') {
    $('#bloqueEditarDatos').show();
    $('#btnGuardarDatosServicio').show();
  }

  if (accion === 'cancelar') {
    $('#bloqueCancelar').show();
    $('#btnConfirmarCancelar').show();
  }

  if (accion === 'cambiar_movil') {
    $('#bloqueMoviles').show();
    cargarMovilesModal();
  }
});

$('#btnGuardarDatosServicio').on('click', async function () {
  const id = $('#modServicioId').val();
  const direccion = $('#modDireccion').val().trim();
  const usuario = $('#modUsuario').val().trim();

  if (!direccion) {
    Swal.fire('Falta dirección', 'Debes ingresar la dirección.', 'warning');
    return;
  }

  if (!usuario) {
    Swal.fire('Falta usuario', 'Debes ingresar el usuario.', 'warning');
    return;
  }

  const ok = await Swal.fire({
    icon: 'question',
    title: 'Guardar cambios',
    text: '¿Deseas actualizar la dirección y el usuario del servicio?',
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'No',
  }).then(r => r.isConfirmed);

  if (!ok) return;

  try {
    const res = await fetch(`{{ url('/servicios/actualizar-datos') }}/${id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        direccion: direccion,
        usuario: usuario
      })
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const raw = await res.text();
      console.error('Respuesta NO JSON:', raw.slice(0, 400));
      throw new Error('El servidor no devolvió JSON.');
    }

    const data = await res.json();

    if (data.success) {
      modalModificarServicio.hide();
      Swal.fire('Listo', data.message || 'Servicio actualizado.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', data.message || 'No fue posible actualizar el servicio.', 'info');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudo actualizar la dirección y el usuario.', 'error');
  }
});

$('#btnConfirmarCancelar').on('click', async function () {
  const id = $('#modServicioId').val();

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
      modalModificarServicio.hide();
      Swal.fire('Listo', data.message || 'Servicio cancelado.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', data.message || 'No fue posible cancelar.', 'info');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudo cancelar el servicio.', 'error');
  }
});

async function cargarMovilesModal() {
  const q = $('#buscarMovilModal').val().trim();
  const params = new URLSearchParams();

  if (q) params.append('q', q);

  $('#tablaMovilesModal').html(`
    <tr>
      <td colspan="5" class="text-muted">Cargando móviles...</td>
    </tr>
  `);

  try {
    const res = await fetch(`{{ route('servicios.moviles') }}?${params.toString()}`, {
      headers: {
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
    const rows = Array.isArray(data) ? data : [];

    if (!rows.length) {
      $('#tablaMovilesModal').html(`
        <tr>
          <td colspan="5" class="text-muted">No se encontraron móviles disponibles.</td>
        </tr>
      `);
      return;
    }

    let html = '';
    rows.forEach(r => {
      html += `
        <tr>
          <td>${esc(r.mo_taxi)}</td>
          <td>${esc(r.placa)}</td>
          <td class="text-start">${esc(r.nombre_conductor)}</td>
          <td>${esc(r.cantidad)}</td>
          <td>
            <button class="btn btn-sm btn-success" onclick="reasignarMovil(${r.mo_id})">
              Seleccionar
            </button>
          </td>
        </tr>
      `;
    });

    $('#tablaMovilesModal').html(html);
  } catch (e) {
    console.error(e);
    $('#tablaMovilesModal').html(`
      <tr>
        <td colspan="5" class="text-danger">Error al cargar móviles.</td>
      </tr>
    `);
  }
}

$('#btnBuscarMovilModal').on('click', cargarMovilesModal);
$('#buscarMovilModal').on('keydown', function(e){
  if (e.key === 'Enter') {
    e.preventDefault();
    cargarMovilesModal();
  }
});

async function reasignarMovil(nuevoConmo) {
  const servicioId = $('#modServicioId').val();

  const ok = await Swal.fire({
    icon: 'question',
    title: 'Cambiar móvil',
    text: '¿Deseas asignar este móvil al servicio?',
    showCancelButton: true,
    confirmButtonText: 'Sí, cambiar',
    cancelButtonText: 'No',
  }).then(r => r.isConfirmed);

  if (!ok) return;

  try {
    const res = await fetch(`{{ url('/servicios/cambiar-movil') }}/${servicioId}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        nuevo_conmo: nuevoConmo
      })
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const raw = await res.text();
      console.error('Respuesta NO JSON:', raw.slice(0, 400));
      throw new Error('El servidor no devolvió JSON.');
    }

    const data = await res.json();

    if (data.success) {
      modalModificarServicio.hide();
      Swal.fire('Listo', data.message || 'Móvil cambiado correctamente.', 'success');
      cargarTabla();
    } else {
      Swal.fire('Sin cambios', data.message || 'No fue posible cambiar el móvil.', 'info');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No se pudo cambiar el móvil.', 'error');
  }
}

$('#btnBuscar').on('click', cargarTabla);
$('#fQuery').on('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    cargarTabla();
  }
});

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