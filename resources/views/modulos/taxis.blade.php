@extends('layouts.app')

@section('content')
<div class="container">
    <div class="form-box bg-light p-4 rounded shadow">
        <h3 class="text-center mb-4">AGREGAR TAXI</h3>

        <form method="POST" action="{{ route('taxis.store') }}" id="formTaxi">
            @csrf

            {{-- PLACA --}}
            <div class="mb-3">
                <label for="placa" class="form-label">PLACA</label>
                <div class="input-group">
                    <input type="text" id="placa" name="placa" class="form-control" value="{{ old('placa') }}" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="verificarPlaca()">🔍</button>
                </div>
                @error('placa')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            {{-- CAMPOS ADICIONALES --}}
            <div id="camposExtra" class="{{ $errors->any() ? '' : 'd-none' }}">
                {{-- MOVIL --}}
                <div class="mb-3">
                    <label for="movil" class="form-label"># MOVIL</label>
                    <select name="movil" id="movil" class="form-select" required>
                        <option value="">Seleccione</option>
                        @foreach($moviles as $m)
                            <option value="{{ $m->ta_movil }}" {{ old('movil') == $m->ta_movil ? 'selected' : '' }}>
                                #{{ $m->ta_movil }}
                            </option>
                        @endforeach
                    </select>
                    @error('movil')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- SOAT --}}
                <div class="mb-3">
                    <label for="soat" class="form-label">SOAT</label>
                    <input type="date" id="soat" name="soat" class="form-control"
                           value="{{ old('soat') }}" required
                           min="{{ \Carbon\Carbon::now()->toDateString() }}">
                    @error('soat')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- TECNOMECÁNICA --}}
                <div class="mb-3">
                    <label for="tecno" class="form-label">TECNOMECÁNICA</label>
                    <input type="date" id="tecno" name="tecno" class="form-control"
                           value="{{ old('tecno') }}" required
                           min="{{ \Carbon\Carbon::now()->toDateString() }}">
                    @error('tecno')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <button type="submit" class="btn btn-primary">GUARDAR</button>
            </div>


        </form>
    </div>
</div>
@endsection

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Mostrar popup si el registro fue exitoso --}}
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'success',
            title: '¡Registro exitoso!',
            text: '{{ session('success') }}',
            confirmButtonText: 'OK'
        });
    });
</script>
@endif

{{-- Mostrar campos y bloquear placa si hubo errores --}}
@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('camposExtra').classList.remove('d-none');
        document.getElementById('placa').readOnly = true;
    });
</script>
@endif

<script>
function verificarPlaca() {
    const placaInput = document.getElementById('placa');
    const placa = placaInput.value.trim().toUpperCase();
    if (!placa) return;

    fetch('/api/verificar-placa?placa=' + placa)
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                Swal.fire({ icon: 'error', title: 'Ya existe', text: 'La placa ya está registrada.' });
                document.getElementById('camposExtra').classList.add('d-none');
            } else {
                Swal.fire({ icon: 'success', title: 'Disponible', text: 'Puedes registrar esta placa.' });
                placaInput.readOnly = true;
                document.getElementById('camposExtra').classList.remove('d-none');
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo verificar la placa.' });
        });
}

// Validación de fechas antes de enviar el formulario
document.getElementById('formTaxi').addEventListener('submit', function(e) {
    const soat = new Date(document.getElementById('soat').value);
    const tecno = new Date(document.getElementById('tecno').value);
    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    if (soat < hoy || tecno < hoy) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Fechas inválidas',
            text: 'La fecha del SOAT y la tecnomecánica deben ser desde hoy en adelante.'
        });
    }
});
</script>
