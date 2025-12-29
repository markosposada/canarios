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
                                <th style="width:60px;">#</th>
                                <th>Movil</th>
                                <th class="col-direccion">Dirección</th>
                                <th style="min-width:100px;">Fecha</th>
                                <th style="min-width:80px;">Hora</th>
                                <th style="min-width:90px;">Operadora</th>
                                <th style="min-width:100px;">Servicio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- estilos solo para esta vista --}}
                <style>
                    /* Para que la dirección no vuelva la tabla inmanejable en celular */
                    #tabla-servicios td.col-direccion, #tabla-servicios th.col-direccion{
                        max-width: 260px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    /* Mejor experiencia de scroll en móviles */
                    .table-responsive{
                        -webkit-overflow-scrolling: touch;
                    }
                </style>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const url = "{{ route('conductor.servicios_asignados.listar') }}";

    function firstN(str, n) {
        if (str === null || str === undefined) return '';
        str = String(str);
        return str.length > n ? str.slice(0, n) : str;
    }

    function escapeHtml(str){
        if (str === null || str === undefined) return '';
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderServicioBadge(servicioValue) {
        const v = parseInt(servicioValue, 10);

        if (v === 1) {
            return `<span class="badge bg-success">ACEPTADO</span>`;
        }
        if (v === 2) {
            return `<span class="badge bg-danger">CANCELADO</span>`;
        }
        return `<span class="badge bg-secondary">DESCONOCIDO</span>`;
    }

    function renderRows(rows, total) {
        const tbody = document.querySelector('#tabla-servicios tbody');
        tbody.innerHTML = '';

        document.getElementById('totalServicios').textContent = String(total ?? (rows?.length ?? 0));

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No tienes servicios asignados.</td></tr>`;
            return;
        }

        rows.forEach((r, idx) => {
            const tr = document.createElement('tr');

            // operadora ya viene recortada desde SQL, pero por seguridad la recortamos igual
            const operadoraFull = r.operadora ?? '';
            const operadoraCorta = firstN(operadoraFull, 8);

            tr.innerHTML = `
                <td class="fw-bold">${idx + 1}</td>
                <td>${escapeHtml(r.movil ?? '')}</td>
                <td class="col-direccion" title="${escapeHtml(r.direccion ?? '')}">
                    ${escapeHtml(r.direccion ?? '')}
                </td>
                <td>${escapeHtml(r.fecha ?? '')}</td>
                <td>${escapeHtml(r.hora ?? '')}</td>
                <td title="${escapeHtml(operadoraFull)}">${escapeHtml(operadoraCorta)}</td>
                <td>${renderServicioBadge(r.servicio)}</td>
            `;
            tbody.appendChild(tr);
        });

        // Si DataTables está cargado en tu template, lo activa
        if (window.$ && $.fn.DataTable) {
            // si ya existe, lo destruimos para volver a pintar sin duplicar
            if ($.fn.DataTable.isDataTable('#tabla-servicios')) {
                $('#tabla-servicios').DataTable().destroy();
            }

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
