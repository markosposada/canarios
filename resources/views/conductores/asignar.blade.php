@extends('layouts.app')

@section('content')
<div class="container">
    <div class="form-box bg-light p-4 rounded shadow">
        <h3 class="text-center mb-4">ASIGNAR CONDUCTOR A TAXI</h3>

        {{-- BUSCAR CÉDULA --}}
        <div class="mb-3">
            <label class="form-label">Buscar por cédula</label>
            <div class="input-group">
                <input type="text" id="buscarCedula" class="form-control">
                <button type="button" class="btn btn-outline-secondary" id="btnBuscar">🔍</button>
            </div>
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

            <button type="submit" class="btn btn-primary">Asignar</button>
        </form>

        {{-- BOTÓN REGRESAR --}}
        <div class="mt-3">
            <a href="{{ url('/dashboard') }}" class="btn btn-secondary">← Regresar</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Asegúrate de que SweetAlert2 esté cargado -->
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputCedula = document.getElementById('buscarCedula');
    const btnBuscar = document.getElementById('btnBuscar');
    const formulario = document.getElementById('formularioAsignar');
    const formCedula = document.getElementById('formCedula');
    const formNombre = document.getElementById('formNombre');
    const cedulaMostrada = document.getElementById('cedulaMostrada');

    btnBuscar.addEventListener('click', function () {
        const cedula = inputCedula.value.trim();

        if (!cedula) {
            Swal.fire('⚠️ Ingresa una cédula', '', 'warning');
            return;
        }

        fetch("{{ url('/buscar-datos-conductor') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ cedula })
        })
        .then(res => res.json())
        .then(data => {
            if (data.encontrado) {
                formulario.classList.remove('d-none');
                formCedula.value = cedula;
                cedulaMostrada.value = cedula;
                formNombre.value = data.nombre;

                inputCedula.disabled = true;

                Swal.fire({
                    icon: 'success',
                    title: 'Conductor encontrado',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                formulario.classList.add('d-none');
                Swal.fire('No se encontró el conductor', '', 'error');
            }
        })
        .catch(() => {
            Swal.fire('Error en la búsqueda', '', 'error');
        });
    });

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
