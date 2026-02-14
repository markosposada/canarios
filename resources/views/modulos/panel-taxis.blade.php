@extends('layouts.app')

@section('content')
<style>
  /* Puedes mover esto al layout si quieres */
  .page-wrap{ max-width: 1100px; }
  .page-title{ font-weight: 800; letter-spacing: .2px; }

  .taxi-card{
    border: 0;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
  }
  .taxi-kv{
    display:flex; justify-content:space-between; gap:10px;
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,.08);
  }
  .taxi-kv:last-child{ border-bottom: 0; }
  .taxi-k{ font-size: 12px; color:#6c757d; }
  .taxi-v{ font-size: 14px; font-weight: 700; text-align:right; word-break: break-word; }

  .estado-pill{
    display:inline-block; padding: 4px 10px; border-radius: 999px;
    font-weight: 800; font-size: 12px;
  }
  .estado-A{ background: rgba(25,135,84,.12); color:#198754; }
  .estado-I{ background: rgba(220,53,69,.12); color:#dc3545; }
  .estado-P{ background: rgba(255,193,7,.18); color:#b58100; }

  @media (max-width:576px){
    .card-body{ padding: 12px; }
  }
</style>

<div class="container py-3 page-wrap">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h3 class="mb-0 page-title">PANEL DE CONTROL DE TAXIS</h3>
  </div>

  {{-- Filtro de búsqueda --}}
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <label for="buscar" class="form-label mb-1">BUSCAR TAXI POR MÓVIL</label>
      <input type="number" id="buscar" class="form-control" placeholder="Ej: 10">
      <small class="text-muted d-block mt-2">Tip: escribe el número del móvil para filtrar rápido.</small>
    </div>
  </div>

  {{-- ✅ Vista móvil/tablet: CARDS --}}
  <div id="listaCards" class="d-lg-none">
    @foreach($taxis as $taxi)
      @php
        $estado = $taxi->ta_estado; // P, A, I
        $estadoTxt = $estado === 'P' ? 'PENDIENTE' : ($estado === 'A' ? 'ACTIVO' : 'INACTIVO');
        $placaTxt = $estado === 'P' ? 'Sin taxi asignado' : $taxi->ta_placa;
      @endphp

      <div class="card taxi-card mb-3 taxi-item" data-movil="{{ $taxi->ta_movil }}">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-bold">Móvil #{{ $taxi->ta_movil }}</div>
              <div class="text-muted" style="font-size:12px">Placa: <span class="fw-semibold">{{ $placaTxt }}</span></div>
            </div>

            <div class="text-end">
              <span class="estado-pill estado-{{ $estado }}">{{ $estadoTxt }}</span>
            </div>
          </div>

          <div class="mt-3">
            <div class="taxi-kv">
              <div class="taxi-k">Estado</div>
              <div class="taxi-v estado-text">{{ $estadoTxt }}</div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
              @if ($taxi->ta_estado === 'P')
                <a href="{{ url('/taxis/crear') }}" class="btn btn-warning btn-sm" target="_blank">ASIGNAR</a>
              @else
                <button
                  class="btn btn-sm btn-toggle-estado btn-{{ $taxi->ta_estado === 'A' ? 'danger' : 'success' }}"
                  data-movil="{{ $taxi->ta_movil }}"
                  data-estado="{{ $taxi->ta_estado }}"
                >
                  {{ $taxi->ta_estado === 'A' ? 'INACTIVAR' : 'ACTIVAR' }}
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- ✅ Vista desktop: TABLA --}}
  <div class="table-responsive d-none d-lg-block">
    <table class="table table-bordered table-striped text-center" id="tablaTaxis">
      <thead class="table-dark">
        <tr>
          <th># MÓVIL</th>
          <th>PLACA</th>
          <th>ESTADO</th>
          <th>ACCIÓN</th>
        </tr>
      </thead>
      <tbody>
        @foreach($taxis as $taxi)
          <tr class="taxi-row" data-movil="{{ $taxi->ta_movil }}">
            <td>{{ $taxi->ta_movil }}</td>
            <td>{{ $taxi->ta_estado === 'P' ? 'Sin taxi asignado' : $taxi->ta_placa }}</td>
            <td class="estado">
              {{ $taxi->ta_estado === 'P' ? 'PENDIENTE' : ($taxi->ta_estado === 'A' ? 'ACTIVO' : 'INACTIVO') }}
            </td>
            <td>
              @if ($taxi->ta_estado === 'P')
                <a href="{{ url('/taxis/crear') }}" class="btn btn-warning btn-sm" target="_blank">ASIGNAR</a>
              @else
                <button
                  class="btn btn-sm btn-toggle-estado btn-{{ $taxi->ta_estado === 'A' ? 'danger' : 'success' }}"
                  data-movil="{{ $taxi->ta_movil }}"
                  data-estado="{{ $taxi->ta_estado }}"
                >
                  {{ $taxi->ta_estado === 'A' ? 'INACTIVAR' : 'ACTIVAR' }}
                </button>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const inputBuscar = document.getElementById('buscar');

  function filtrar() {
    const filtro = (inputBuscar.value || '').trim().toLowerCase();

    // Tabla
    document.querySelectorAll('.taxi-row').forEach(row => {
      const movil = String(row.getAttribute('data-movil') || '').toLowerCase();
      row.style.display = movil.includes(filtro) ? '' : 'none';
    });

    // Cards
    document.querySelectorAll('.taxi-item').forEach(card => {
      const movil = String(card.getAttribute('data-movil') || '').toLowerCase();
      card.style.display = movil.includes(filtro) ? '' : 'none';
    });
  }

  inputBuscar.addEventListener('keyup', filtrar);

  // ======= Actualiza UI (tabla + card) con el nuevo estado =======
  function estadoTxt(estado){
    return estado === 'A' ? 'ACTIVO' : 'INACTIVO';
  }

  function actualizarUI(movil, nuevoEstado) {
    // TABLA
    const fila = document.querySelector(`.taxi-row[data-movil="${movil}"]`);
    if (fila) {
      const estadoCelda = fila.querySelector('.estado');
      const btn = fila.querySelector('.btn-toggle-estado');
      if (estadoCelda) estadoCelda.textContent = estadoTxt(nuevoEstado);
      if (btn) {
        btn.dataset.estado = nuevoEstado;
        if (nuevoEstado === 'A') {
          btn.textContent = 'INACTIVAR';
          btn.classList.remove('btn-success');
          btn.classList.add('btn-danger');
        } else {
          btn.textContent = 'ACTIVAR';
          btn.classList.remove('btn-danger');
          btn.classList.add('btn-success');
        }
      }
    }

    // CARD
    const card = document.querySelector(`.taxi-item[data-movil="${movil}"]`);
    if (card) {
      const pill = card.querySelector('.estado-pill');
      const estadoText = card.querySelector('.estado-text');
      const btn = card.querySelector('.btn-toggle-estado');

      if (estadoText) estadoText.textContent = estadoTxt(nuevoEstado);

      if (pill) {
        pill.classList.remove('estado-A','estado-I','estado-P');
        pill.classList.add('estado-' + nuevoEstado);
        pill.textContent = estadoTxt(nuevoEstado);
      }

      if (btn) {
        btn.dataset.estado = nuevoEstado;
        if (nuevoEstado === 'A') {
          btn.textContent = 'INACTIVAR';
          btn.classList.remove('btn-success');
          btn.classList.add('btn-danger');
        } else {
          btn.textContent = 'ACTIVAR';
          btn.classList.remove('btn-danger');
          btn.classList.add('btn-success');
        }
      }
    }
  }

  // ======= Click activar/inactivar (delegación para que funcione en ambas vistas) =======
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-toggle-estado');
    if (!btn) return;

    const movil = btn.dataset.movil;
    const estadoActual = btn.dataset.estado; // 'A' o 'I'
    const accion = (estadoActual === 'A') ? 'inactivar' : 'activar';

    const ok = await Swal.fire({
      icon: 'question',
      title: `¿Deseas ${accion} el móvil ${movil}?`,
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'No'
    }).then(r => r.isConfirmed);

    if (!ok) return;

    try {
      Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      const res = await fetch("{{ route('taxis.cambiarEstado') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ movil })
      });

      if (res.status === 419) {
        Swal.fire('Sesión expirada', 'Recarga la página e intenta de nuevo.', 'warning');
        return;
      }

      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        const raw = await res.text();
        console.error('Respuesta NO JSON:', raw.slice(0, 400));
        Swal.fire('Error', 'El servidor no devolvió JSON. Revisa la ruta / auth.', 'error');
        return;
      }

      const data = await res.json(); // {estado:'A'|'I'}
      actualizarUI(movil, data.estado);

      Swal.fire({
        icon: 'success',
        title: '¡Actualizado!',
        text: `El estado del taxi quedó en ${estadoTxt(data.estado)}`
      });

    } catch (err) {
      console.error(err);
      Swal.fire('Error', 'No se pudo cambiar el estado.', 'error');
    }
  });
});
</script>
@endsection
