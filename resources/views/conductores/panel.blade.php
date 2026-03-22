@extends('layouts.app')

@section('content')
<style>
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
  .pill-ret{ background: rgba(220,53,69,.12); color:#dc3545; }

  .kv{
    display:flex; justify-content:space-between; gap:10px;
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,.08);
  }
  .kv:last-child{ border-bottom: 0; }
  .k{ font-size: 12px; color:#6c757d; }
  .v{ font-size: 14px; font-weight: 700; text-align:right; }

  .acciones-grid{
    display:grid;
    grid-template-columns: 1fr;
    gap:8px;
  }

  .acciones-mobile{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:8px;
  }

  .acciones-mobile .full{
    grid-column: 1 / -1;
  }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0 page-title">PANEL DE CONTROL DE CONDUCTORES</h2>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-9">
          <label class="form-label mb-1">Buscar por móvil</label>
          <input
            type="text"
            id="movil"
            class="form-control"
            placeholder="Ej: 10"
            inputmode="numeric"
            pattern="[0-9]*"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
          >
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
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

  function getEstadoInfo(estado){
    estado = Number(estado);

    if (estado === 1) {
      return {
        texto: 'ACTIVO',
        pill: 'pill-on',
        claseTexto: 'text-success fw-bold',
        fila: 'table-success'
      };
    }

    if (estado === 3) {
      return {
        texto: 'RETIRADO',
        pill: 'pill-ret',
        claseTexto: 'text-danger fw-bold',
        fila: 'table-danger'
      };
    }

    return {
      texto: 'INACTIVO',
      pill: 'pill-off',
      claseTexto: 'text-secondary',
      fila: 'table-light'
    };
  }

  function getBotonEstado(item){
    const estado = Number(item.mo_estado);
    const id = item.mo_id;

    if (estado === 1) {
      return `<button class="btn btn-sm btn-warning btn-accion" data-id="${id}" data-accion="desactivar">Inactivar</button>`;
    }

    if (estado === 2) {
      return `<button class="btn btn-sm btn-success btn-accion" data-id="${id}" data-accion="activar">Activar</button>`;
    }

    return `<button class="btn btn-sm btn-success btn-accion" data-id="${id}" data-accion="activar">Reactivar</button>`;
  }

  function getBotonRetirar(item){
    const estado = Number(item.mo_estado);
    const id = item.mo_id;

    if (estado === 3) {
      return `<span class="text-danger fw-bold">Retirado</span>`;
    }

    return `<button class="btn btn-sm btn-danger btn-accion" data-id="${id}" data-accion="retirar">Retirar</button>`;
  }

  function renderResultados(data){
    let html = '';

    if (!Array.isArray(data) || data.length === 0) {
      html = '<div class="alert alert-warning text-center mb-0">No se encontraron registros.</div>';
      $('#tablaResultados').html(html);
      return;
    }

    // ===== MOBILE =====
    html += `<div class="d-md-none">`;
    data.forEach(item => {
      const est = getEstadoInfo(item.mo_estado);
      const botonEstado = getBotonEstado(item);
      const botonRetirar = getBotonRetirar(item);

      html += `
        <div class="card driver-card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-bold">Móvil #${escapeHtml(item.mo_taxi)}</div>
                <div class="text-muted" style="font-size:12px">
                  Conductor: <span class="fw-semibold">${escapeHtml(item.conduc_nombres)}</span>
                </div>
              </div>
              <div class="text-end">
                <span class="pill ${est.pill}">${est.texto}</span>
              </div>
            </div>

            <div class="mt-3">
              <div class="kv">
                <div class="k">Estado</div>
                <div class="v">${est.texto}</div>
              </div>

              <div class="mt-3 acciones-mobile">
                <div>
                  <div class="small text-muted mb-1">Cambio estado</div>
                  ${botonEstado}
                </div>
                <div>
                  <div class="small text-muted mb-1">Retirar</div>
                  ${botonRetirar}
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    html += `</div>`;

    // ===== DESKTOP =====
    html += `
      <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered text-center align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th style="width:120px">MÓVIL</th>
              <th>CONDUCTOR</th>
              <th style="width:140px">ESTADO</th>
              <th style="width:170px">CAMBIO ESTADO</th>
              <th style="width:140px">RETIRAR</th>
            </tr>
          </thead>
          <tbody>
    `;

    data.forEach(item => {
      const est = getEstadoInfo(item.mo_estado);
      const botonEstado = getBotonEstado(item);
      const botonRetirar = getBotonRetirar(item);

      html += `
        <tr class="${est.fila}">
          <td>${escapeHtml(item.mo_taxi)}</td>
          <td class="text-start">${escapeHtml(item.conduc_nombres)}</td>
          <td class="${est.claseTexto}">${est.texto}</td>
          <td>${botonEstado}</td>
          <td>${botonRetirar}</td>
        </tr>
      `;
    });

    html += `
          </tbody>
        </table>
      </div>
    `;

    $('#tablaResultados').html(html);
  }

  function cargarTabla(movil = '') {
    $('#tablaResultados').html('<div class="text-muted text-center py-3">Cargando...</div>');

    $.ajax({
      url: '{{ route("conductores.buscarAjax") }}',
      type: 'GET',
      dataType: 'json',
      data: { movil: movil },
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
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

  function getTituloAccion(accion){
    if (accion === 'activar') return 'ACTIVAR';
    if (accion === 'desactivar') return 'INACTIVAR';
    if (accion === 'retirar') return 'RETIRAR';
    return 'ACTUALIZAR';
  }

  function getTextoExito(accion){
    if (accion === 'activar') return 'Registro activado correctamente';
    if (accion === 'desactivar') return 'Registro inactivado correctamente';
    if (accion === 'retirar') return 'Registro retirado correctamente';
    return 'Operación realizada correctamente';
  }

  async function cambiarEstado(id, accion) {
    const ok = await Swal.fire({
      icon: accion === 'retirar' ? 'warning' : 'question',
      title: `¿Deseas ${getTituloAccion(accion)} este conductor?`,
      text: accion === 'retirar' ? 'El estado pasará a RETIRADO.' : '',
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'No'
    }).then(r => r.isConfirmed);

    if (!ok) return;

    try {
      Swal.fire({
        title:'Actualizando...',
        allowOutsideClick:false,
        didOpen: () => Swal.showLoading()
      });

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
              text: getTextoExito(accion),
              timer: 1400,
              showConfirmButton: false
            });

            const movil = $('#movil').val().trim();
            cargarTabla(movil);
          } else {
            Swal.fire('Error', response.message || 'No se pudo cambiar el estado.', 'error');
          }
        },
        error: function(xhr) {
          console.error('ERROR cambiarEstado:', xhr.status, xhr.responseText);

          let mensaje = 'Fallo al procesar la solicitud.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            mensaje = xhr.responseJSON.message;
          }

          if (xhr.status === 419) {
            Swal.fire('Sesión expirada', 'Recarga la página e intenta de nuevo.', 'warning');
            return;
          }

          Swal.fire('Error', mensaje, 'error');
        }
      });

    } catch (e) {
      console.error(e);
      Swal.fire('Error', 'Fallo al procesar la solicitud.', 'error');
    }
  }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-accion');
    if (!btn) return;

    const id = btn.dataset.id;
    const accion = btn.dataset.accion;

    if (!id || !accion) return;

    cambiarEstado(id, accion);
  });

  $(document).ready(function() {
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