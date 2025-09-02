@extends('layouts.app')

@section('content')
<div class="container">
    <div class="form-box bg-light p-4 rounded shadow">
        <h3 class="text-center mb-4">AGREGAR CONDUCTOR</h3>

        <form method="POST" action="{{ route('conductores.store') }}" id="formConductor">
            @csrf

            {{-- CÉDULA --}}
            <div class="mb-3">
                <label for="cedula" class="form-label">CÉDULA</label>
                <div class="input-group">
                    <input type="text" id="cedula" name="cedula" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="verificarCedula()">🔍</button>
                </div>
            </div>

            {{-- CAMPOS EXTRA --}}
            <div id="camposExtra" class="d-none">
                <div class="mb-3">
                    <label class="form-label">DATOS PERSONALES</label>
                    <input type="text" id="nombre" class="form-control" disabled>
                </div>

                <div class="mb-3">
                    <label for="licencia" class="form-label">NÚMERO DE LICENCIA</label>
                    <input type="text" id="licencia" name="licencia" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="fecha_licencia" class="form-label">VENCIMIENTO DE LICENCIA</label>
                    <input type="date" id="fecha_licencia" name="fecha_licencia" class="form-control"
                        min="{{ \Carbon\Carbon::now()->toDateString() }}" required>
                </div>

                <button type="submit" class="btn btn-primary">GUARDAR</button>
            </div>

            <a href="/" class="btn btn-secondary mt-3">VOLVER</a>
        </form>
    </div>
</div>
@endsection

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function verificarCedula() {
    const cedula = document.getElementById('cedula').value.trim();
    if (!cedula) return;

    fetch('/api/buscar-cedula?cedula=' + cedula)
        .then(res => res.json())
        .then(data => {
            if (data.existe) {
                Swal.fire({
                    icon: 'success',
                    title: 'Conductor encontrado',
                    text: 'Ingrese los datos adicionales.'
                });
                document.getElementById('cedula').readOnly = true;
                document.getElementById('camposExtra').classList.remove('d-none');
                document.getElementById('nombre').value = data.nombre;

            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No encontrado',
                    html: `${data.mensaje} <br><br><a href='/register' class='btn btn-sm btn-primary'>Registrar Usuario</a>`,
                    showConfirmButton: false
                });
                document.getElementById('camposExtra').classList.add('d-none');
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo verificar la cédula.'
            });
        });
}
</script>
