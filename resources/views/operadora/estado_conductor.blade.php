@extends('layouts.app')

@section('content')
<div class="container">
  <div class="form-box bg-light p-4 rounded shadow">
    <h3 class="text-center mb-4">ESTADO CONDUCTOR</h3>

    <div class="mb-3">
      <label class="form-label fw-bold">Buscar conductor</label>
      <div class="input-group">
        <input type="text" id="qConductor" class="form-control" placeholder="Buscar por nombre o cédula...">
        <button type="button" class="btn btn-outline-secondary" id="btnBuscar">
          Buscar
        </button>
      </div>
      <small class="text-muted">Escribe y se buscará automáticamente (mínimo 2 caracteres).</small>
    </div>

    <div id="msg"></div>

    <div class="table-responsive">
      <table class="table table-sm table-striped" id="tablaConductores">
        <thead>
          <tr>
            <th style="width:140px;">Cédula</th>
            <th>Nombre</th>
            <th style="width:140px;">Estado</th>
            <th style="width:240px;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr><td colspan="4" class="text-muted text-center">Escribe para buscar...</td></tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      <a href="{{ url('/dashboard') }}" class="btn btn-secondary">← Regresar</a>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlBuscar = "{{ route('operadora.estado_conductor.buscar') }}";
  const urlActualizar = "{{ route('operadora.estado_conductor.actualizar') }}";

  const qInput = document.getElementById('qConductor');
  const btnBuscar = document.getElementById('btnBuscar');
  const tbody = document.querySelector('#tablaConductores tbody');
  const msg = document.getElementById('msg');

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

  function badgeEstado(e){
    e = Number(e);
    if(e === 1) return 'badge badge-success';
    if(e === 2) return 'badge badge-secondary';
    if(e === 3) return 'badge badge-danger';
    if(e === 4) return 'badge badge-dark';
    return 'badge badge-light';
  }

  function showMsg(html, cls='alert alert-info'){
    msg.innerHTML = `<div class="${cls} py-2">${html}</div>`;
  }

  let t = null;

  function dispararBusqueda(){
    const q = qInput.value.trim();
    clearTimeout(t);

    if(q.length < 2){
      tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Escribe al menos 2 caracteres...</td></tr>`;
      msg.innerHTML = '';
      return;
    }

    t = setTimeout(()=> buscar(q), 250);
  }

  qInput.addEventListener('input', dispararBusqueda);
  btnBuscar.addEventListener('click', dispararBusqueda);

  function buscar(q){
    tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center">Buscando...</td></tr>`;
    msg.innerHTML = '';

    fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(j => {
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
            <td title="${esc(r.nombre)}">${esc(r.nombre)}</td>
            <td><span class="${badgeEstado(r.estado)}">${esc(estadoTexto(r.estado))}</span></td>
            <td>
              <div class="d-flex" style="gap:6px;">
                <button class="btn btn-sm btn-success btnSet" data-cc="${esc(r.cedula)}" data-est="1">Activo</button>
                <button class="btn btn-sm btn-danger btnSet" data-cc="${esc(r.cedula)}" data-est="2">Inactivo</button>
                <button class="btn btn-sm btn-dark btnSet" data-cc="${esc(r.cedula)}" data-est="4">Ret.</button>
              </div>
            </td>
          </tr>
        `);
      });

      document.querySelectorAll('.btnSet').forEach(b => {
        b.addEventListener('click', function(){
          const cedula = this.getAttribute('data-cc');
          const estado = this.getAttribute('data-est');
          actualizarEstado(cedula, estado);
        });
      });
    })
    .catch(() => {
      tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center">Error buscando</td></tr>`;
      showMsg('❌ Error buscando', 'alert alert-danger');
    });
  }

  function actualizarEstado(cedula, estado){
    Swal.fire({
      icon: 'question',
      title: 'Confirmar cambio',
      text: `¿Cambiar estado del conductor ${cedula} a ${estadoTexto(estado)}?`,
      showCancelButton: true,
      confirmButtonText: 'Sí, cambiar',
      cancelButtonText: 'Cancelar'
    }).then(res => {
      if(!res.isConfirmed) return;

      fetch(urlActualizar, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ cedula, estado: Number(estado) })
      })
      .then(r => r.json().then(data => ({ok:r.ok, data})))
      .then(({ok, data}) => {
        if(!ok || !data.success){
          throw new Error(data.message || 'No se pudo actualizar');
        }
        showMsg(`✅ Estado actualizado: <b>${esc(cedula)}</b> → <b>${esc(estadoTexto(data.estado))}</b>`, 'alert alert-success');
        // refresca tabla con el mismo texto de búsqueda
        buscar(qInput.value.trim());
      })
      .catch(e => {
        showMsg('❌ ' + (e.message || 'Error actualizando'), 'alert alert-danger');
      });
    });
  }
});
</script>
@endsection
