@extends('layouts.app_conductor')

@section('title', 'Servicios asignados')

@section('content')
<div class="row">
    <div class="col-12">
        <h3 class="mb-3">SERVICIOS ASIGNADOS</h3>
    </div>
</div>

{{-- Resumen --}}
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Servicios</p>
                    <h4 class="mb-0" id="totalServicios">0</h4>
                </div>
                <i class="mdi mdi-taxi mdi-36px text-warning"></i>
            </div>
        </div>
    </div>
</div>

{{-- Tabla --}}
<div class="row">
    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resumen Servicios</h4>

                <div class="table-responsive">
                    <table class="table table-striped" id="tabla-servicios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Movil</th>
                                <th>Dirección</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Operadora</th>
                                <th>Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const url = "{{ route('conductor.servicios_asignados.listar') }}";

    function renderRows(rows, total) {
        const tbody = document.querySelector('#tabla-servicios tbody');
        tbody.innerHTML = '';

        document.getElementById('totalServicios').textContent = String(total ?? (rows?.length ?? 0));

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No tienes servicios asignados.</td></tr>`;
            return;
        }

        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${r.fac_id ?? ''}</td>
                <td>${r.fac_movil ?? ''}</td>
                <td>${r.fac_direc ?? ''}</td>
                <td>${r.fac_fecha ?? ''}</td>
                <td>${r.fac_hora ?? ''}</td>
                <td>${r.fac_operadora ?? ''}</td>
                <td>${r.pago ?? ''}</td>
            `;
            tbody.appendChild(tr);
        });

        // Si DataTables está cargado en tu template, lo activa
        if (window.$ && $.fn.DataTable && !$.fn.DataTable.isDataTable('#tabla-servicios')) {
            $('#tabla-servicios').DataTable({
                responsive: true,
                pageLength: 10,
                order: []
            });
        }
    }

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(res => res.json())
    .then(json => renderRows(json.data, json.total))
    .catch(() => {
        const tbody = document.querySelector('#tabla-servicios tbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error cargando servicios.</td></tr>`;
    });
});
</script>
@endsection
