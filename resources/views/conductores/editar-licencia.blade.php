@extends('layouts.app')

@section('content')
<style>
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
    <h3 class="mb-0 page-title">EDITAR CONDUCTOR</h3>
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
      <form action="{{ route('conductores.actualizarLicencia') }}" method="POST" id="formularioLicencia" class="d-none">
        @csrf

        {{-- Cédula original oculta (para buscar registro) --}}
        <input type="hidden" name="cedula_original" id="cedulaOriginal">

        <div class="row g-2">

          {{-- CÉDULA EDITABLE --}}
          <div class="col-12 col-md-4">
            <label class="form-label">Cédula</label>
            <input type="text" name="cedula_nueva" id="cedulaNueva" class="form-control mono" required>
            <div class="muted-sm mt-1">Puedes modificar la cédula si es necesario.</div>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Nombre completo</label>
            <input type="text" name="nombres" id="formNombre" class="form-control" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Número de licencia</label>
            <input type="text" name="licencia" id="formLicencia" class="form-control" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Fecha de vencimiento</label>
            <input type="date" name="fecha" id="formFecha" class="form-control" required>
          </div>

          {{-- ✅ CAMBIO DE CONTRASEÑA (OPCIONAL) --}}
          <div class="col-12 mt-2">
            <hr>
            <h5 class="mb-1">Contraseña de ingreso</h5>
            <div class="muted-sm">Solo llena esto si quieres cambiar la contraseña del conductor.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Nueva contraseña</label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" minlength="6" autocomplete="new-password" placeholder="Dejar vacío si no cambia">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="btnTogglePass">👁</button>
              </div>
            </div>
            <div class="muted-sm mt-1">Mínimo 6 caracteres.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="6" autocomplete="new-password" placeholder="Repite la contraseña">
          </div>

          <div class="col-12 d-flex align-items-end gap-2 mt-2">
            <button type="submit" class="btn btn-primary btn-touch">
              <i class="mdi mdi-content-save"></i> Guardar cambios
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

{{-- MODAL BUSCAR CONDUCTOR --}}
<div class="modal fade" id="modalBuscarConductor" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Buscar conductor</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">
        <input type="text" id="qConductor" class="form-control" placeholder="Nombre o cédula">

        <div class="table-responsive mt-3">
          <table class="table table-sm table-striped">
            <thead class="table-dark">
              <tr>
                <th>Cédula</th>
                <th>Nombre</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="tablaConductores">
              <tr><td colspan="3" class="text-center text-muted">Escribe para buscar...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){

  const urlBuscar = "{{ route('conductores.licencia.buscar') }}";

  const qInput = document.getElementById('qConductor');
  const tabla = document.getElementById('tablaConductores');
  const formulario = document.getElementById('formularioLicencia');

  const cedulaOriginal = document.getElementById('cedulaOriginal');
  const cedulaNueva = document.getElementById('cedulaNueva');
  const formNombre = document.getElementById('formNombre');
  const formLicencia = document.getElementById('formLicencia');
  const formFecha = document.getElementById('formFecha');
  const conductorPreview = document.getElementById('conductorPreview');

  const pass = document.getElementById('password');
  const pass2 = document.getElementById('password_confirmation');
  const btnTogglePass = document.getElementById('btnTogglePass');

  document.getElementById('btnAbrirBuscar').addEventListener('click', function(){
      $('#modalBuscarConductor').modal('show');
  });

  // Mostrar/ocultar contraseña
  if(btnTogglePass){
    btnTogglePass.addEventListener('click', function(){
      const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
      pass.setAttribute('type', type);
      pass2.setAttribute('type', type);
    });
  }

  let tmr = null;
  qInput.addEventListener('input', function(){
      clearTimeout(tmr);
      let q = this.value.trim();
      if(q.length < 2){
          tabla.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Mínimo 2 caracteres</td></tr>';
          return;
      }

      tmr = setTimeout(() => {
        fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
          headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            const rows = data.data || [];
            tabla.innerHTML = '';

            if(rows.length === 0){
                tabla.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin resultados</td></tr>';
                return;
            }

            rows.forEach(c => {
                tabla.innerHTML += `
                  <tr>
                    <td>${c.cedula}</td>
                    <td>${c.nombre}</td>
                    <td>
                      <button type="button" class="btn btn-sm btn-primary btnSel" data-cc="${c.cedula}">
                        Seleccionar
                      </button>
                    </td>
                  </tr>`;
            });

            document.querySelectorAll('.btnSel').forEach(btn=>{
                btn.addEventListener('click', function(){
                    seleccionar(this.dataset.cc);
                });
            });
        })
        .catch(()=> {
          tabla.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error buscando</td></tr>';
        });
      }, 200);
  });

  function seleccionar(cedula){
      fetch("{{ route('conductores.licencia.detalle') }}", {
          method:'POST',
          headers:{
              'Content-Type':'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'X-Requested-With':'XMLHttpRequest',
              'Accept':'application/json'
          },
          body: JSON.stringify({cedula})
      })
      .then(r=>r.json())
      .then(data=>{
          if(!data.encontrado){
              Swal.fire('No encontrado','','error');
              return;
          }

          formulario.classList.remove('d-none');

          cedulaOriginal.value = data.cedula;
          cedulaNueva.value = data.cedula;

          formNombre.value = data.nombres || '';
          formLicencia.value = data.licencia || '';
          formFecha.value = data.fecha || '';

          // limpiar password al seleccionar
          pass.value = '';
          pass2.value = '';

          conductorPreview.value = (data.nombres || '') + ' ('+(data.cedula || '')+')';

          $('#modalBuscarConductor').modal('hide');
      })
      .catch(() => Swal.fire('Error','No se pudo cargar el conductor','error'));
  }

  document.getElementById('btnLimpiar').addEventListener('click', function(){
      formulario.classList.add('d-none');
      cedulaOriginal.value='';
      cedulaNueva.value='';
      formNombre.value='';
      formLicencia.value='';
      formFecha.value='';
      pass.value='';
      pass2.value='';
      conductorPreview.value='';
  });

  @if(session('success'))
    Swal.fire('Correcto','{{ session('success') }}','success');
  @endif

  @if(session('error'))
    Swal.fire('Error','{{ session('error') }}','error');
  @endif

});
</script>
@endsection