@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="text-center mb-4">PANEL DE CONTROL DE TAXIS</h3>

    {{-- Filtro de búsqueda --}}
    <div class="mb-3">
        <label for="buscar" class="form-label">BUSCAR TAXI POR MÓVIL</label>
        <input type="number" id="buscar" class="form-control" placeholder="Ej: 10">
    </div>

    {{-- Tabla --}}
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
    <tr data-movil="{{ $taxi->ta_movil }}">
        <td>{{ $taxi->ta_movil }}</td>
        <td>
            {{ $taxi->ta_estado === 'P' ? 'Sin taxi asignado' : $taxi->ta_placa }}
        </td>
        <td class="estado">
            {{ $taxi->ta_estado === 'P' ? 'PENDIENTE' : ($taxi->ta_estado === 'A' ? 'ACTIVO' : 'INACTIVO') }}
        </td>
        <td>
            @if ($taxi->ta_estado === 'P')
                <a href="{{ url('/taxis/crear') }}" class="btn btn-warning btn-sm" target="_blank">
                    ASIGNAR
                </a>
            @else
                <button class="btn btn-sm btn-toggle-estado btn-{{ $taxi->ta_estado === 'A' ? 'danger' : 'success' }}"
                    data-movil="{{ $taxi->ta_movil }}">
                    {{ $taxi->ta_estado === 'A' ? 'INACTIVAR' : 'ACTIVAR' }}
                </button>
            @endif
        </td>
    </tr>
@endforeach

        </tbody>
    </table>
</div>
@endsection

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Filtro por móvil
    document.getElementById('buscar').addEventListener('keyup', function () {
        const filtro = this.value.trim().toLowerCase();
        const filas = document.querySelectorAll('#tablaTaxis tbody tr');

        filas.forEach(fila => {
            const movil = fila.getAttribute('data-movil').toLowerCase();
            fila.style.display = movil.includes(filtro) ? '' : 'none';
        });
    });

    // Botón activar/inactivar
    document.querySelectorAll('.btn-toggle-estado').forEach(btn => {
        btn.addEventListener('click', function () {
            const movil = this.dataset.movil;
            fetch("{{ route('taxis.cambiarEstado') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ movil })
            })
            .then(res => res.json())
            .then(data => {
                const fila = document.querySelector(`tr[data-movil="${movil}"]`);
                const estadoCelda = fila.querySelector('.estado');
                const btn = fila.querySelector('.btn-toggle-estado');

                if (data.estado === 'A') {
                    estadoCelda.textContent = 'ACTIVO';
                    btn.textContent = 'INACTIVAR';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-danger');
                } else {
                    estadoCelda.textContent = 'INACTIVO';
                    btn.textContent = 'ACTIVAR';
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-success');
                }

                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: `El estado del taxi ha sido cambiado a ${data.estado === 'A' ? 'ACTIVO' : 'INACTIVO'}`
                });
            });
        });
    });
});
</script>
