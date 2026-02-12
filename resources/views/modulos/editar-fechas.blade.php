@extends('layouts.app')

@section('content')
<div class="container">
    <div class="form-box bg-light p-4 rounded shadow">
        <h3 class="text-center mb-4">EDITAR FECHAS DEL TAXI</h3>

        <form method="POST" action="{{ route('taxis.updateDates') }}" id="formFechas">
            @csrf

            {{-- PLACA ACTUAL (BUSCAR) --}}
            <div class="mb-3">
                <label for="placa" class="form-label">PLACA ACTUAL</label>
                <div class="input-group">
                    <input type="text" id="placa" name="placa" class="form-control" value="{{ old('placa') }}" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="buscarPlaca()">🔍</button>
                </div>
                @error('placa')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            {{-- CAMPOS EXTRA: FECHAS + PLACA NUEVA --}}
            <div id="camposFechas" class="d-none">

                {{-- PLACA NUEVA --}}
                <div class="mb-3">
                    <label for="placa_nueva" class="form-label">PLACA NUEVA (opcional)</label>
                    <input type="text" id="placa_nueva" name="placa_nueva" class="form-control"
                           value="{{ old('placa_nueva') }}"
                           placeholder="Si deseas cambiarla, escríbela aquí">
                    @error('placa_nueva')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- SOAT --}}
                <div class="mb-3">
                    <label for="soat" class="form-label">SOAT</label>
                    <input type="date" id="soat" name="soat" class="form-control"
                           value="{{ old('soat') }}"
                           min="{{ \Carbon\Carbon::now()->toDateString() }}" required>
                    @error('soat')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- TECNOMECÁNICA --}}
                <div class="mb-3">
                    <label for="tecno" class="form-label">TECNOMECÁNICA</label>
                    <input type="date" id="tecno" name="tecno" class="form-control"
                           value="{{ old('tecno') }}"
                           min="{{ \Carbon\Carbon::now()->toDateString() }}" required>
                    @error('tecno')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <button type="submit" class="btn btn-primary">ACTUALIZAR</button>
            </div>

            
        </form>
    </div>
</div>
@endsection

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'success',
            title: '¡Actualización exitosa!',
            text: '{{ session('success') }}',
            confirmButtonText: 'OK'
        });
    });
</script>
@endif

@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('camposFechas').classList.remove('d-none');
        // dejamos la placa actual bloqueada si hay errores para no cambiar el registro objetivo
        document.getElementById('placa').readOnly = true;
    });
</script>
@endif

<script>
function buscarPlaca() {
    const placaInput = document.getElementById('placa');
    const placa = placaInput.value.trim().toUpperCase();
    if (!placa) return;

    fetch('/api/buscar-taxi?placa=' + placa)
        .then(response => response.json())
        .then(data => {
            if (data.taxi) {
                Swal.fire({ icon: 'success', title: 'Taxi encontrado', text: 'Puedes modificar fechas y/o placa.' });

                placaInput.readOnly = true;
                document.getElementById('camposFechas').classList.remove('d-none');

                document.getElementById('soat').value = data.taxi.ta_soat;
                document.getElementById('tecno').value = data.taxi.ta_tecno;

                // sugerimos la misma placa como nueva (editable)
                document.getElementById('placa_nueva').value = data.taxi.ta_placa;
            } else {
                Swal.fire({ icon: 'error', title: 'No encontrado', text: 'No existe un taxi con esa placa.' });
                document.getElementById('camposFechas').classList.add('d-none');
            }
        });
}

// Validación frontend fechas
document.getElementById('formFechas').addEventListener('submit', function(e) {
    const soat = new Date(document.getElementById('soat').value);
    const tecno = new Date(document.getElementById('tecno').value);
    const hoy = new Date(); hoy.setHours(0, 0, 0, 0);

    if (soat < hoy || tecno < hoy) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Fechas inválidas',
            text: 'Las fechas deben ser iguales o posteriores a hoy.'
        });
    }
});
</script>
