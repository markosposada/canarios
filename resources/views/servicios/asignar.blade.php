@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 text-center">ASIGNA SERVICIOS</h2>

    {{-- Formulario: Usuario -> Barrio (con datalist) -> Dirección --}}
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Cliente / Usuario</label>
            <input type="text" id="inpUsuario" class="form-control" placeholder="Nombre del cliente" autofocus>
        </div>

        <div class="col-md-4">
            <label class="form-label">Barrio</label>
            <input type="text"
                   id="inpBarrio"
                   class="form-control"
                   placeholder="Barrio/Urbanización"
                   list="barriosList"
                   autocomplete="off">
            <datalist id="barriosList"></datalist>
            <input type="hidden" id="barrioId"> {{-- opcional: guarda el id del barrio si coincide con una sugerencia --}}
        </div>

        <div class="col-md-4">
            <label class="form-label">Dirección</label>
            <input type="text" id="inpDireccion" class="form-control" placeholder="Calle, No, etc.">
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button id="btnAbrirModal" class="btn btn-dark">Asignar</button>
        <button id="btnLimpiar" class="btn btn-outline-secondary">Limpiar</button>
    </div>
</div>

{{-- Modal: Lista de móviles activos (ordenados por menor cantidad), con buscador AJAX --}}
<div class="modal fade" id="modalMoviles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div class="w-100">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0">Seleccione el móvil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="mt-3">
                <input type="text" id="inpBuscarMovil" class="form-control" placeholder="Buscar por móvil, conductor o placa...">
            </div>
        </div>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center" id="tablaMoviles">
                <thead class="table-dark">
                    <tr>
                        <th>MÓVIL</th>
                        <th>CONDUCTOR</th>
                        <th>PLACA</th>
                        <th>CANTIDAD (hoy)</th>
                        <th>ACCIÓN</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <small class="text-muted">Ordenado por menor cantidad de servicios.</small>
      </div>
    </div>
  </div>
</div>

{{-- Librerías --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ======= Setup ======= */
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
const OPERADORA = @json(Auth::user()->name ?? 'operadora');

/* ======= Validaciones básicas ======= */
function validarFormulario() {
    const usuario   = $('#inpUsuario').val().trim();
    const direccion = $('#inpDireccion').val().trim();
    if (!usuario || !direccion) {
        Swal.fire('Faltan datos', 'Usuario y Dirección son obligatorios.', 'warning');
        return false;
    }
    return true;
}

/* ======= MOVILES (modal) ======= */
function cargarMoviles(q = '') {
    return $.get('{{ route("servicios.moviles") }}', { q });
}

function pintarTabla(moviles) {
    const $tbody = $('#tablaMoviles tbody').empty();
    if (!moviles.length) {
        $tbody.append('<tr><td colspan="5" class="text-muted">No hay móviles activos.</td></tr>');
        return;
    }
    moviles.forEach(m => {
        $tbody.append(`
            <tr>
                <td>${m.mo_taxi}</td>
                <td>${m.nombre_conductor ?? ''}</td>
                <td>${m.placa ?? ''}</td>
                <td>${m.cantidad}</td>
                <td><button class="btn btn-sm btn-success" onclick="asignar(${m.mo_id})">Disponible</button></td>
            </tr>
        `);
    });
}

async function abrirModal(q = '') {
    if (!validarFormulario()) return;
    try {
        const moviles = await cargarMoviles(q);
        pintarTabla(moviles);
        const modal = new bootstrap.Modal(document.getElementById('modalMoviles'));
        modal.show();
        // foco al buscador del modal
        setTimeout(() => document.getElementById('inpBuscarMovil')?.focus(), 300);
    } catch {
        Swal.fire('Error', 'No fue posible cargar los móviles.', 'error');
    }
}

async function asignar(mo_id) {
    const usuario   = $('#inpUsuario').val().trim();
    const direccion = $('#inpDireccion').val().trim();
    const barrioTxt = $('#inpBarrio').val().trim();
    const barrioId  = $('#barrioId').val();

    try {
        await $.post('{{ route("servicios.registrar") }}', {
            conmo: mo_id,                 // ⚠️ Si dis_conmo es mo_conductor, ajusta backend y aquí
            usuario: usuario,
            direccion: direccion,
            barrio: barrioTxt,            // texto
            barrio_id: barrioId,          // id (opcional)
            operadora: OPERADORA
        });
        Swal.fire('Asignado', 'Servicio registrado correctamente.', 'success');
        const modalEl = document.getElementById('modalMoviles');
        bootstrap.Modal.getInstance(modalEl).hide();
    } catch {
        Swal.fire('Error', 'No se pudo registrar el servicio.', 'error');
    }
}

/* ======= BARRIO: <datalist> + AJAX (debounce) ======= */
let debounceBarrio = null;

/** Rellena el <datalist> con resultados **/
function renderBarrios(options) {
  const dl = document.getElementById('barriosList');
  dl.innerHTML = '';
  options.forEach(o => {
    const opt = document.createElement('option');
    opt.value = o.nombre;          // lo que el usuario ve/elige
    opt.setAttribute('data-id', o.id);
    dl.appendChild(opt);
  });
}

/** Busca sugerencias en el servidor **/
async function buscarBarrios(q) {
  if (!q || q.length < 2) { // evita consultas con 1 letra
    renderBarrios([]);
    return;
  }
  try {
    const url = `{{ route('barrios.sugerencias') }}?q=${encodeURIComponent(q)}`;
    const res = await fetch(url);
    const data = await res.json();
    renderBarrios(data);
  } catch (e) {
    // silencioso; puedes loguear si quieres
  }
}

/** Dispara búsqueda mientras se escribe (debounce 200ms) **/
document.getElementById('inpBarrio').addEventListener('input', function() {
  const val = this.value.trim();
  clearTimeout(debounceBarrio);
  debounceBarrio = setTimeout(() => buscarBarrios(val), 200);
  // Si el usuario cambia el texto, resetea el id hasta que seleccione uno válido
  document.getElementById('barrioId').value = '';
});

/** Cuando el usuario selecciona exactamente un barrio de la lista, guardamos su id **/
document.getElementById('inpBarrio').addEventListener('change', function() {
  const val = this.value.trim().toLowerCase();
  const opts = Array.from(document.querySelectorAll('#barriosList option'));
  const match = opts.find(o => o.value.trim().toLowerCase() === val);
  document.getElementById('barrioId').value = match ? (match.getAttribute('data-id') || '') : '';
});

/* ======= Listeners generales ======= */
// Botón Asignar abre modal
$('#btnAbrirModal').on('click', () => abrirModal());

// ENTER en Dirección abre modal
$('#inpDireccion').on('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        abrirModal();
    }
});

// Enter en Barrio pasa el foco a Dirección
$('#inpBarrio').on('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        $('#inpDireccion').focus();
    }
});

// Buscador instantáneo dentro del modal (debounce)
let debounceMovil = null;
$('#modalMoviles').on('shown.bs.modal', function () {
    $('#inpBuscarMovil').off('input').on('input', function () {
        const q = $(this).val();
        clearTimeout(debounceMovil);
        debounceMovil = setTimeout(async () => {
            try {
                const moviles = await cargarMoviles(q);
                pintarTabla(moviles);
            } catch {}
        }, 200);
    });
});

// Limpiar formulario
$('#btnLimpiar').on('click', () => {
    $('#inpUsuario, #inpBarrio, #barrioId, #inpDireccion').val('');
    $('#inpUsuario').focus();
});
</script>
@endsection
