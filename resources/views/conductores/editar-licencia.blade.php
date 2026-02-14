@extends('layouts.app')

@section('content')
<div class="container">
    <div class="form-box bg-light p-4 rounded shadow">
        <h3 class="text-center mb-4">EDITAR LICENCIA DE CONDUCTOR</h3>

        {{-- BUSCAR CÉDULA --}}
        <div class="mb-3">
            <label class="form-label">Buscar por cédula</label>
            <div class="input-group">
                <input type="number" id="buscarCedula" class="form-control">
                <button type="button" class="btn btn-outline-secondary" id="btnBuscar">🔍</button>
            </div>
        </div>

        {{-- FORMULARIO OCULTO --}}
        <form action="{{ route('conductores.actualizarLicencia') }}" method="POST" id="formularioLicencia" class="d-none">
            @csrf

            <input type="hidden" name="cedula" id="formCedula">

            <div class="mb-3">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="nombres" id="formNombre" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Número de licencia</label>
                <input type="number" name="licencia" id="formLicencia" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha de vencimiento</label>
                <input type="date" name="fecha" id="formFecha" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>
    </div>
</div>


@endsection

@section('scripts')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputCedula = document.getElementById('buscarCedula');
    const btnBuscar = document.getElementById('btnBuscar');
    const formulario = document.getElementById('formularioLicencia');

    // Disparar búsqueda al presionar Enter en el input
    inputCedula.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            btnBuscar.click();
        }
    });

    btnBuscar.addEventListener('click', function () {
        const cedula = inputCedula.value.trim();

        if (!cedula) {
            Swal.fire("Ups", "Ingresa una cédula para buscar.", "warning");
            return;
        }

        fetch("{{ url('/buscar-conductor') }}", {
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
                inputCedula.disabled = true;

                document.getElementById('formCedula').value = cedula;
                document.getElementById('formNombre').value = data.nombres;
                document.getElementById('formNombre').disabled = false;
                document.getElementById('formLicencia').value = data.licencia;
                document.getElementById('formFecha').value = data.fecha;

                Swal.fire({
                    icon: 'success',
                    title: 'Conductor encontrado',
                    text: data.nombres,
                    confirmButtonText: 'Aceptar'
                });

            } else {
                formulario.classList.add('d-none');
                Swal.fire("No encontrado", data.error || "Conductor no encontrado", "error");
            }
        })
        .catch(() => {
            Swal.fire("Error", "Ocurrió un error al buscar el conductor.", "error");
        });
    });

    // Mostrar mensaje al guardar si hay éxito
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '{{ session('success') }}',
        confirmButtonText: 'Aceptar'
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}',
        confirmButtonText: 'Aceptar'
    });
    @endif
});
</script>
@endsection
