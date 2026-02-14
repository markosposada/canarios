@extends('layouts.app')

@section('content')
<style>
  /* Puedes mover esto al layout */
  .page-wrap{ max-width: 1100px; }
  .page-title{ font-weight:800; letter-spacing:.2px; }

  .card-soft{
    border:0;
    border-radius:14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
  }

  .muted-sm{ font-size: 12px; color:#6c757d; }
  .mono{ font-variant-numeric: tabular-nums; }

  .cell-ellipsis{
    max-width: 420px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .btn-touch{ border-radius:12px; font-weight:700; }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h3 class="mb-0 page-title">ASIGNAR CONDUCTOR A TAXI</h3>
    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm">← Regresar</a>
  </div>

  <div class="card card-soft">
    <div class="card-body">

      {{-- BUSCAR CONDUCTOR --}}
      <div class="mb-3">
        <label class="form-label fw-bold">Conductor</label>
        <div class="input-group">
          <input type="text" id="conductorPreview" class="form-control" placeholder="Selecciona un conductor..." readonly>
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary" id="btnAbrirBuscar">
              <i class="mdi mdi-account-search"></i> Buscar
            </button>
          </div>
        </div>
        <div class="muted-sm mt-1">Busca por nombre o cédula.</div>
      </div>

      {{-- FORMULARIO --}}
      <form action="{{ url('/asignar-conductor') }}" method="POST" id="formularioAsignar" class="d-none">
        @csrf
        <input type="hidden" name="cedula" id="formCedula">

        <div class="row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label">Cédula</label>
            <input type="text" id="cedulaMostrada" class="form-control mono" disabled>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Nombre completo</label>
            <input type="text" id="formNombre" class="form-control" disabled>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Móvil disponible</label>
            <select name="movil" id="selectMovil" class="form-control" required>
              <option value="">-- Seleccionar móvil --</option>
              @foreach ($moviles as $movil)
                <option value="{{ $movil->ta_movil }}">{{ $movil->ta_movil }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-8 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary btn-touch">
              <i class="mdi mdi-content-save"></i> Asignar
            </button>

            <button type="button" class="btn btn-outline-secondary btn-touch" id="btnLimpiar">
              Limpiar
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

{{-- MODAL BUSCAR CONDUCTOR (Bootstrap 4) --}}
<div class="modal fade" id="modalBuscarConductor" tabindex="-1" role="dialog" aria-hidden="true">
  {{-- ✅ sin modal-lg para móvil --}}
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title mb-0">Buscar conductor</h5>
        {{-- ✅ Bootstrap 4 close --}}
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label fw-bold">Escribe nombre o cédula</label>
          <input type="text" id="qConductor" class="form-control" placeholder="Ej: Adán / 123456">
          <div class="muted-sm mt-1">Se buscará mientras escribes (mínimo 2 caracteres).</div>
        </div>

        <div id="buscMsg" class="mt-2"></div>

        {{-- MOBILE: Cards --}}
        <div class="d-md-none mt-3" id="listaConductoresCards">
          <div class="text-muted text-center py-3">Escribe para buscar...</div>
        </div>

        {{-- DESKTOP: Tabla --}}
        <div class="table-responsive d-none d-md-block mt-3">
          <table class="table table-sm table-striped" id="tablaConductores">
            <thead class="table-dark">
              <tr>
                <th style="width:140px;">Cédula</th>
                <th>Nombre</th>
                <th style="width:120px;">Estado</th>
                <th style="width:140px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="4" class="text-muted text-center">Escribe para buscar...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlBuscar = "{{ route('conductores.asignar.buscar') }}";

  const btnAbrir = document.getElementById('btnAbrirBuscar');
  const qInput = document.getElementById('qConductor');
  const buscMsg = document.getElementById('buscMsg');

  // Desktop table
  const tbody = document.querySelector('#tablaConductores tbody');
  // Mobile cards
  const listaCards = document.getElementById('listaConductoresCards');

  const formulario = document.getElementById('formularioAsignar');
  const formCedula = document.getElementById('formCedula');
  const formNombre = document.getElementById('formNombre');
  const cedulaMostrada = document.getElementById('cedulaMostrada');
  const conductorPreview = document.getElementById('conductorPreview');
  const btnLimpiar = document.getElementById('btnLimpiar');
  const selectMovil = document.getElementById('selectMovil');

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function estadoTexto(e){
    e = Number(e);
    if(e === 1) return 'ACTIVO';
    if(e === 2) return 'INACTIVO';
    if(e === 3) return 'SANCIONADO';
    if(e === 4) return 'RETIRADO';
    return '—';
  }

  function showBuscMsg(html, cls='alert alert-info'){
    buscMsg.innerHTML = `<div class="${cls} py-2 mb-0">${html}</div>`;
  }
  function clearBuscMsg(){ buscMsg.innerHTML = ''; }

  function abrirModal(){
    if (!window.$) {
      Swal.fire('Error', 'No se encontró jQuery en el layout.', 'error');
      return;
    }
    $('#modalBuscarConductor').modal('show');
    setTimeout(()=> qInput && qInput.focus(), 250);
  }

  function cerrarModal(){
    if (window.$) $('#modalBuscarConductor').modal('hide');
  }

  btnAbrir.addEventListener('click', abrirModal);

  // Reset buscador al cerrar
  if (window.$) {
    $('#modalBuscarConductor').on('hidden.bs.modal', function(){
      qInput.value = '';
      clearBuscMsg();
      // reset resultados
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Escribe para buscar...</td></tr>`;
      listaCards.innerHTML = `<div class="text-muted text-center py-3">Escribe para buscar...</div>`;
    });
  }

  let t = null;
  qInput.addEventListener('input', function(){
    const q = qInput.value.trim();
    clearTimeout(t);

    if(q.length < 2){
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Escribe al menos 2 caracteres...</td></tr>`;
      listaCards.innerHTML = `<div class="text-muted text-center py-3">Escribe al menos 2 caracteres...</div>`;
      clearBuscMsg();
      return;
    }

    t = setTimeout(()=> buscar(q), 250);
  });

  function renderDesktop(rows){
    if(rows.length === 0){
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Sin resultados</td></tr>`;
      return;
    }
    tbody.innerHTML = '';
    rows.forEach(r => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td class="mono">${esc(r.cedula)}</td>
          <td class="cell-ellipsis" title="${esc(r.nombre)}">${esc(r.nombre)}</td>
          <td>${esc(estadoTexto(r.estado))}</td>
          <td class="text-right">
            <button type="button" class="btn btn-sm btn-primary btnSel" data-cc="${esc(r.cedula)}">
              Seleccionar
            </button>
          </td>
        </tr>
      `);
    });
  }

  function renderMobile(rows){
    if(rows.length === 0){
      listaCards.innerHTML = `<div class="text-muted text-center py-3">Sin resultados</div>`;
      return;
    }
    let html = '';
    rows.forEach(r => {
      html += `
        <div class="card card-soft mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between gap-2">
              <div>
                <div class="fw-bold">${esc(r.nombre)}</div>
                <div class="muted-sm">Cédula: <span class="mono fw-semibold">${esc(r.cedula)}</span></div>
                <div class="muted-sm">Estado: <b>${esc(estadoTexto(r.estado))}</b></div>
              </div>
              <div class="text-end">
                <button type="button" class="btn btn-primary btn-touch btnSel" data-cc="${esc(r.cedula)}">
                  Seleccionar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    listaCards.innerHTML = html;
  }

  function bindSeleccion(){
    document.querySelectorAll('.btnSel').forEach(b => {
      b.addEventListener('click', function(){
        const cc = this.getAttribute('data-cc');
        seleccionar(cc);
      });
    });
  }

  function buscar(q){
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Buscando...</td></tr>`;
    listaCards.innerHTML = `<div class="text-muted text-center py-3">Buscando...</div>`;
    clearBuscMsg();

    fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }
    })
    .then(r => {
      if (r.status === 419) throw new Error('Sesión expirada (419)');
      return r.json();
    })
    .then(j => {
      // Soporta 2 formatos:
      // A) { ok:true, data:[...] }
      // B) { data:[...] }
      const ok = (typeof j.ok === 'undefined') ? true : !!j.ok;
      if(!ok) throw new Error('No se pudo buscar');

      const rows = j.data || [];
      renderDesktop(rows);
      renderMobile(rows);
      bindSeleccion();
    })
    .catch(e => {
      console.error(e);
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error buscando</td></tr>`;
      listaCards.innerHTML = `<div class="text-danger text-center py-3">Error buscando</div>`;
      showBuscMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    });
  }

  function seleccionar(cedula){
    // usa tu endpoint actual (POST)
    fetch("{{ url('/buscar-datos-conductor') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ cedula })
    })
    .then(r => {
      if (r.status === 419) throw new Error('Sesión expirada (419)');
      return r.json();
    })
    .then(data => {
      if(!data.encontrado){
        Swal.fire('No se encontró el conductor', '', 'error');
        return;
      }

      formulario.classList.remove('d-none');
      formCedula.value = data.cedula;
      cedulaMostrada.value = data.cedula;
      formNombre.value = data.nombre;

      conductorPreview.value = `${data.nombre} (${data.cedula})`;
      cerrarModal();

      Swal.fire({
        icon: 'success',
        title: 'Conductor seleccionado',
        timer: 900,
        showConfirmButton: false
      });
    })
    .catch(e => {
      console.error(e);
      Swal.fire('Error cargando datos', e.message || '', 'error');
    });
  }

  if(btnLimpiar){
    btnLimpiar.addEventListener('click', function(){
      formulario.classList.add('d-none');
      formCedula.value = '';
      cedulaMostrada.value = '';
      formNombre.value = '';
      conductorPreview.value = '';
      if(selectMovil) selectMovil.value = '';
    });
  }

  @if(session('success'))
    Swal.fire({
      icon: 'success',
      title: '{{ session('success') }}',
      confirmButtonText: 'Aceptar'
    });
  @endif
});
</script>
@endsection
