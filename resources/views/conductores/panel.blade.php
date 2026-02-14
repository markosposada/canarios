@extends('layouts.app')

@section('content')
<style>
  /* Puedes mover esto al layout */
  .page-wrap{ max-width: 1100px; }
  .page-title{ font-weight: 800; letter-spacing: .2px; }

  .driver-card{
    border: 0;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
  }
  .pill{
    display:inline-block; padding: 4px 10px; border-radius: 999px;
    font-weight: 800; font-size: 12px;
  }
  .pill-on{ background: rgba(25,135,84,.12); color:#198754; }
  .pill-off{ background: rgba(108,117,125,.18); color:#6c757d; }

  .kv{
    display:flex; justify-content:space-between; gap:10px;
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,.08);
  }
  .kv:last-child{ border-bottom: 0; }
  .k{ font-size: 12px; color:#6c757d; }
  .v{ font-size: 14px; font-weight: 700; text-align:right; }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0 page-title">PANEL DE CONTROL DE CONDUCTORES</h2>
  </div>

  {{-- Buscador --}}
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-9">
          <label class="form-label mb-1">Buscar por móvil</label>
          <input type="text" id="movil" class="form-control" placeholder="Ej: 10">
        </div>
        <div class="col-12 col-md-3">
          <button id="btnBuscar" class="btn btn-dark w-100">Buscar</button>
        </div>
      </div>
      <small class="text-muted d-block mt-2">Tip: presiona Enter para buscar.</small>
    </div>
  </div>

  <div id="tablaResultados"></div>
</div>

{{-- SweetAlert2 y jQuery --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  // Setup CSRF
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
  });

  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function renderResultados(data){
    let html = '';

    if (!Array.isArray(data) || data.length === 0) {
      html = '<div class="alert alert-warning text-center mb-0">No se encontraron registros.</div>';
      $('#tablaResultados').html(html);
      return;
    }

    // ====== MOBILE: cards ======
    html += `<div class="d-md-none">`;
    data.forEach(item => {
      const activo = Number(item.mo_estado) === 1;
      const estadoTxt = activo ? 'ACTIVO' : 'INACTIVO';
      const pill = activo ? 'pill-on' : 'pill-off';

      const boton = activo
        ? `<button class="btn btn-sm btn-danger w-100" data-id="${item.mo_id}" data-accion="desactivar">Inactivar</button>`
        : `<button class="btn btn-sm btn-success w-100" data-id="${item.mo_id}" data-accion="activar">Activar</button>`;

      html += `
        <div class="card driver-card mb-3 driver-item">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-bold">Móvil #${escapeHtml(item.mo_taxi)}</div>
                <div class="text-muted" style="font-size:12px">Conductor: <span class="fw-semibold">${escapeHtml(item.conduc_nombres)}</span></div>
              </div>
              <div class="text-end">
                <span class="pill ${pill}">${estadoTxt}</span>
              </div>
            </div>

            <div class="mt-3">
              <div class="kv"><div class="k">Estado</div><div class="v">${estadoTxt}</div></div>
              <div class="mt-3">
                ${boton}
              </div>
            </div>
          </div>
        </div>
      `;
    });
    html += `</div>`;

    // ====== DESKTOP: table ======
    html += `
      <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered text-center align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:120px">MÓVIL</th>
              <th>CONDUCTOR</th>
              <th style="width:140px">ESTADO</th>
              <th style="width:160px">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
    `;

    data.forEach(item => {
      const activo = Number(item.mo_estado) === 1;
      const estadoTexto = activo ? 'ACTIVO' : 'INACTIVO';
      const estadoColor = activo ? 'text-success fw-bold' : 'text-secondary';
      const filaColor = activo ? 'table-success' : 'table-light';

      const boton = activo
        ? `<button class="btn btn-sm btn-danger btn-accion" data-id="${item.mo_id}" data-accion="desactivar">Inactivar</button>`
        : `<button class="btn btn-sm btn-success btn-accion" data-id="${item.mo_id}" data-accion="activar">Activar</button>`;

      html += `
        <tr class="${filaColor}">
          <td>${escapeHtml(item.mo_taxi)}</td>
          <td class="text-start">${escapeHtml(item.conduc_nombres)}</td>
          <td class="${estadoColor}">${estadoTexto}</td>
          <td>${boton}</td>
        </tr>
      `;
    });

    html += `</tbody></table></div>`;

    $('#tablaResultados').html(html);
  }

  // Cargar tabla (ajax)
  function cargarTabla(movil = '') {
    $('#tablaResultados').html('<div class="text-muted text-center py-3">Cargando...</div>');

    $.ajax({
      url: '{{ route("conductores.buscarAjax") }}',
      type: 'GET',
      dataType: 'json',
      data: { movil: movil },
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      success: function(data) {
        renderResultados(data);
      },
      error: function(xhr) {
        console.error('ERROR cargarTabla:', xhr.status, xhr.responseText);

        if (xhr.status === 419) {
          Swal.fire('Sesión expirada', 'Recarga la página e intenta de nuevo.', 'warning');
          return;
        }

        Swal.fire('Error', 'No se pudo cargar la tabla.', 'error');
        $('#tablaResultados').html('<div class="alert alert-danger text-center mb-0">Error al cargar los datos.</div>');
      }
    });
  }

  // Cambiar estado
  async function cambiarEstado(id, accion) {
    const ok = await Swal.fire({
      icon: 'question',
      title: `¿Deseas ${accion === 'activar' ? 'ACTIVAR' : 'INACTIVAR'} este conductor?`,
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'No'
    }).then(r => r.isConfirmed);

    if (!ok) return;

    try {
      Swal.fire({ title:'Actualizando...', allowOutsideClick:false, didOpen: () => Swal.showLoading() });

      $.ajax({
        url: `/conductores/movil/${id}/estado`,
        type: 'POST',
        dataType: 'json',
        data: { accion: accion },
        success: function(response) {
          if (response.success) {
            Swal.fire({
              icon: 'success',
              title: 'Éxito',
              text: `Registro ${accion} correctamente`,
              timer: 1200,
              showConfirmButton: false
            });

            const movil = $('#movil').val().trim();
            cargarTabla(movil);
          } else {
            Swal.fire('Error', 'No se pudo cambiar el estado.', 'error');
          }
        },
        error: function(xhr) {
          console.error('ERROR cambiarEstado:', xhr.status, xhr.responseText);
          if (xhr.status === 419) {
            Swal.fire('Sesión expirada', 'Recarga la página e intenta de nuevo.', 'warning');
            return;
          }
          Swal.fire('Error', 'Fallo al procesar la solicitud.', 'error');
        }
      });

    } catch (e) {
      console.error(e);
      Swal.fire('Error', 'Fallo al procesar la solicitud.', 'error');
    }
  }

  // Delegación: botones Activar/Inactivar (sirve para cards y tabla)
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-accion, .driver-item button');
    if (!btn) return;

    const id = btn.dataset.id;
    const accion = btn.dataset.accion;
    if (!id || !accion) return;

    cambiarEstado(id, accion);
  });

  $(document).ready(function() {
    cargarTabla(); // carga todos

    $('#btnBuscar').click(function() {
      const movil = $('#movil').val().trim();
      cargarTabla(movil);
    });

    $('#movil').on('keypress', function(e) {
      if (e.which == 13) {
        e.preventDefault();
        const movil = $('#movil').val().trim();
        cargarTabla(movil);
      }
    });
  });
</script>
@endsection
