@extends('layouts.app')

@section('content')
<div class="container">
  <div class="form-box bg-light p-4 rounded shadow">
    <h3 class="text-center mb-4">ASIGNAR CONDUCTOR A TAXI</h3>

    {{-- BUSCAR CONDUCTOR --}}
    <div class="mb-3">
      <label class="form-label fw-bold">Conductor</label>
      <div class="input-group">
        <input type="text" id="conductorPreview" class="form-control" placeholder="Selecciona un conductor..." readonly>
        <button type="button" class="btn btn-outline-secondary" id="btnAbrirBuscar">
          <i class="mdi mdi-account-search"></i> Buscar
        </button>
      </div>
      <small class="text-muted">Busca por nombre o cédula, como en recaudo.</small>
    </div>

    {{-- FORMULARIO --}}
    <form action="{{ url('/asignar-conductor') }}" method="POST" id="formularioAsignar" class="d-none">
      @csrf
      <input type="hidden" name="cedula" id="formCedula">

      <div class="mb-3">
        <label class="form-label">Cédula</label>
        <input type="text" id="cedulaMostrada" class="form-control" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Nombre completo</label>
        <input type="text" id="formNombre" class="form-control" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Móvil disponible</label>
        <select name="movil" id="selectMovil" class="form-control" required>
          <option value="">-- Seleccionar móvil --</option>
          @foreach ($moviles as $movil)
            <option value="{{ $movil->ta_movil }}">{{ $movil->ta_movil }}</option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="mdi mdi-content-save"></i> Asignar
      </button>

      <button type="button" class="btn btn-outline-secondary ms-2" id="btnLimpiar">
        Limpiar
      </button>
    </form>

    {{-- BOTÓN REGRESAR --}}
    <div class="mt-3">
      <a href="{{ url('/dashboard') }}" class="btn btn-secondary">← Regresar</a>
    </div>
  </div>
</div>

{{-- MODAL BUSCAR CONDUCTOR --}}
<div class="modal fade" id="modalBuscarConductor" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buscar conductor</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Escribe nombre o cédula</label>
          <input type="text" id="qConductor" class="form-control" placeholder="Ej: Adán / 123456">
          <small class="text-muted">Se buscará mientras escribes.</small>
        </div>

        <div id="buscMsg"></div>

        <div class="table-responsive">
          <table class="table table-sm table-striped" id="tablaConductores">
            <thead>
              <tr>
                <th style="width:140px;">Cédula</th>
                <th>Nombre</th>
                <th style="width:120px;">Estado</th>
                <th style="width:120px;"></th>
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
  const urlDatos  = "{{ route('conductores.datos') }}";

  const modalEl = document.getElementById('modalBuscarConductor');
  const btnAbrir = document.getElementById('btnAbrirBuscar');
  const qInput = document.getElementById('qConductor');
  const tbody = document.querySelector('#tablaConductores tbody');
  const buscMsg = document.getElementById('buscMsg');

  const formulario = document.getElementById('formularioAsignar');
  const formCedula = document.getElementById('formCedula');
  const formNombre = document.getElementById('formNombre');
  const cedulaMostrada = document.getElementById('cedulaMostrada');
  const conductorPreview = document.getElementById('conductorPreview');
  const btnLimpiar = document.getElementById('btnLimpiar');

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
    buscMsg.innerHTML = `<div class="${cls} py-2">${html}</div>`;
  }

  function abrirModal(){
    // Bootstrap 4 (tu template usa data-dismiss)
    window.$ && $('#modalBuscarConductor').modal('show');
    setTimeout(()=> qInput.focus(), 250);
  }

  function cerrarModal(){
    window.$ && $('#modalBuscarConductor').modal('hide');
  }

  btnAbrir.addEventListener('click', abrirModal);

  let t = null;
  qInput.addEventListener('input', function(){
    const q = qInput.value.trim();
    clearTimeout(t);

    if(q.length < 2){
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Escribe al menos 2 caracteres...</td></tr>`;
      buscMsg.innerHTML = '';
      return;
    }

    t = setTimeout(()=> buscar(q), 250);
  });

  function buscar(q){
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Buscando...</td></tr>`;
    buscMsg.innerHTML = '';

    fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(j => {
      if(!j.ok) throw new Error('No se pudo buscar');

      const rows = j.data || [];
      if(rows.length === 0){
        tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Sin resultados</td></tr>`;
        return;
      }

      tbody.innerHTML = '';
      rows.forEach(r => {
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${esc(r.cedula)}</td>
            <td class="td-ellipsis" title="${esc(r.nombre)}">${esc(r.nombre)}</td>
            <td>${esc(estadoTexto(r.estado))}</td>
            <td class="text-right">
              <button type="button" class="btn btn-sm btn-primary btnSel" data-cc="${esc(r.cedula)}">
                Seleccionar
              </button>
            </td>
          </tr>
        `);
      });

      document.querySelectorAll('.btnSel').forEach(b => {
        b.addEventListener('click', function(){
          const cc = this.getAttribute('data-cc');
          seleccionar(cc);
        });
      });
    })
    .catch(e => {
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error buscando</td></tr>`;
      showBuscMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    });
  }

  function seleccionar(cedula){
    fetch("{{ url('/buscar-datos-conductor') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ cedula })
    })
    .then(r => r.json())
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
        timer: 1200,
        showConfirmButton: false
      });
    })
    .catch(()=> Swal.fire('Error cargando datos', '', 'error'));
  }

  if(btnLimpiar){
    btnLimpiar.addEventListener('click', function(){
      formulario.classList.add('d-none');
      formCedula.value = '';
      cedulaMostrada.value = '';
      formNombre.value = '';
      conductorPreview.value = '';
      document.getElementById('selectMovil').value = '';
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
