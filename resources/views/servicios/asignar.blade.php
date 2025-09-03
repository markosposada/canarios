@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 text-center">ASIGNA SERVICIOS</h2>

    {{-- Formulario: Usuario -> Barrio -> Dirección --}}
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
            <input type="hidden" id="barrioId"> {{-- guarda el id del barrio si coincide con sugerencia --}}
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

{{-- Modal: Lista de móviles activos --}}
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
    const barrio    = $('#inpBarrio').val().trim();
    const direccion = $('#inpDireccion').val().trim();

    if (!usuario || !barrio || !direccion) {
        Swal.fire('Faltan datos', 'Usuario, Barrio y Dirección son obligatorios.', 'warning');
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

async function asignar(mo_id) {
    const usuario   = $('#inpUsuario').val().trim();
    const direccion = $('#inpDireccion').val().trim();
    const barrioTxt = $('#inpBarrio').val().trim();
    const barrioId  = $('#barrioId').val();

try {
  const resp = await $.post('{{ route("servicios.registrar") }}', {
    conmo: mo_id,
    usuario, direccion,
    barrio: barrioTxt,
    barrio_id: barrioId,
    operadora: OPERADORA
  });

  const token = resp.token || '---'; // ahora 3 dígitos

  Swal.fire({
    icon: 'success',
    title: 'Servicio asignado',
    html: `<div style="font-size:14px">Código para consulta (24h):</div>
           <div style="font-size:32px;font-weight:800;letter-spacing:3px">${token}</div>`,
  });

  // Cerrar modal
  const modalEl = document.getElementById('modalMoviles');
  bootstrap.Modal.getInstance(modalEl).hide();

  // Limpiar inputs
  $('#inpUsuario, #inpBarrio, #barrioId, #inpDireccion').val('');
  $('#inpUsuario').focus();

} catch {
  Swal.fire('Error', 'No se pudo registrar el servicio.', 'error');
}
}


/* ======= BARRIO: <datalist> + AJAX (debounce) ======= */
let debounceBarrio = null;
function renderBarrios(options) {
  const dl = document.getElementById('barriosList');
  dl.innerHTML = '';
  options.forEach(o => {
    const opt = document.createElement('option');
    opt.value = o.nombre;
    opt.setAttribute('data-id', o.id);
    dl.appendChild(opt);
  });
}
async function buscarBarrios(q) {
  if (!q || q.length < 2) { renderBarrios([]); return; }
  try {
    const url = `{{ route('barrios.sugerencias') }}?q=${encodeURIComponent(q)}`;
    const res = await fetch(url);
    const data = await res.json();
    renderBarrios(data);
  } catch {}
}
document.getElementById('inpBarrio').addEventListener('input', function() {
  const val = this.value.trim();
  clearTimeout(debounceBarrio);
  debounceBarrio = setTimeout(() => buscarBarrios(val), 200);
  document.getElementById('barrioId').value = '';
});
document.getElementById('inpBarrio').addEventListener('change', function() {
  const val = this.value.trim().toLowerCase();
  const opts = Array.from(document.querySelectorAll('#barriosList option'));
  const match = opts.find(o => o.value.trim().toLowerCase() === val);
  document.getElementById('barrioId').value = match ? (match.getAttribute('data-id') || '') : '';
});

/* ======= Modal y buscador interno ======= */
function debounce(fn, wait = 200) {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
}

$('#btnAbrirModal').on('click', async () => {
  if (!validarFormulario()) return;
  try {
    const moviles = await cargarMoviles('');
    pintarTabla(moviles);
    const modal = new bootstrap.Modal(document.getElementById('modalMoviles'));
    modal.show();
    setTimeout(() => document.getElementById('inpBuscarMovil')?.focus(), 300);
    $('#inpBuscarMovil').val('');
  } catch {
    Swal.fire('Error', 'No fue posible cargar los móviles.', 'error');
  }
});

$('#inpUsuario').on('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    $('#inpBarrio').focus();
  }
});

$('#inpDireccion').on('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); $('#btnAbrirModal').click(); }
});

$('#inpBarrio').on('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); $('#inpDireccion').focus(); }
});

const filtrarMoviles = debounce(async function () {
  const q = $('#inpBuscarMovil').val();
  try {
    $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-muted">Buscando...</td></tr>');
    const moviles = await cargarMoviles(q);
    pintarTabla(moviles);
  } catch {
    $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-danger">Error al buscar</td></tr>');
  }
}, 250);
$('#inpBuscarMovil').on('input', filtrarMoviles);
$('#inpBuscarMovil').on('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });

$('#btnLimpiar').on('click', () => {
  $('#inpUsuario, #inpBarrio, #barrioId, #inpDireccion').val('');
  $('#inpUsuario').focus();
});
</script>
@endsection
