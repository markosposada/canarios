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

{{-- Tabla escritorio / Cards móvil --}}
<div class="row">
    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resumen Servicios</h4>

                {{-- Vista escritorio --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped" id="tabla-servicios">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Móvil</th>
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

                {{-- Vista móvil --}}
                <div class="d-block d-md-none" id="cards-servicios">
                    <div class="text-center text-muted py-3">Cargando...</div>
                </div>

                <style>
                    #tabla-servicios td.col-direccion,
                    #tabla-servicios th.col-direccion{
                        max-width: 260px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .table-responsive{
                        -webkit-overflow-scrolling: touch;
                    }

                    .servicio-card{
                        border: 1px solid rgba(0,0,0,.08);
                        border-radius: 14px;
                        padding: 14px;
                        margin-bottom: 12px;
                        box-shadow: 0 2px 8px rgba(0,0,0,.04);
                        background: #fff;
                    }

                    .servicio-card-head{
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                        margin-bottom: 10px;
                    }

                    .servicio-card-num{
                        font-weight: 700;
                        font-size: 15px;
                    }

                    .servicio-card-body .fila{
                        margin-bottom: 8px;
                        line-height: 1.25;
                    }

                    .servicio-card-body .label{
                        font-weight: 700;
                        color: #222;
                        display: block;
                        margin-bottom: 2px;
                    }

                    .servicio-card-body .valor{
                        color: #555;
                        word-break: break-word;
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

    function renderTable(rows, total) {
        const tbody = document.querySelector('#tabla-servicios tbody');
        tbody.innerHTML = '';

        document.getElementById('totalServicios').textContent = String(total ?? (rows?.length ?? 0));

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No tienes servicios asignados.</td></tr>`;
            return;
        }

        rows.forEach((r, idx) => {
            const tr = document.createElement('tr');

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

        if (window.$ && $.fn.DataTable) {
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

    function renderCards(rows, total) {
        const contenedor = document.getElementById('cards-servicios');
        contenedor.innerHTML = '';

        document.getElementById('totalServicios').textContent = String(total ?? (rows?.length ?? 0));

        if (!rows || rows.length === 0) {
            contenedor.innerHTML = `<div class="text-center text-muted py-3">No tienes servicios asignados.</div>`;
            return;
        }

        rows.forEach((r, idx) => {
            const operadoraFull = r.operadora ?? '';
            const operadoraCorta = firstN(operadoraFull, 8);

            contenedor.insertAdjacentHTML('beforeend', `
                <div class="servicio-card">
                    <div class="servicio-card-head">
                        <div class="servicio-card-num">Servicio #${idx + 1}</div>
                        <div>${renderServicioBadge(r.servicio)}</div>
                    </div>

                    <div class="servicio-card-body">
                        <div class="fila">
                            <span class="label">Móvil</span>
                            <span class="valor">${escapeHtml(r.movil ?? '')}</span>
                        </div>

                        <div class="fila">
                            <span class="label">Dirección</span>
                            <span class="valor">${escapeHtml(r.direccion ?? '')}</span>
                        </div>

                        <div class="fila">
                            <span class="label">Fecha</span>
                            <span class="valor">${escapeHtml(r.fecha ?? '')}</span>
                        </div>

                        <div class="fila">
                            <span class="label">Hora</span>
                            <span class="valor">${escapeHtml(r.hora ?? '')}</span>
                        </div>

                        <div class="fila">
                            <span class="label">Operadora</span>
                            <span class="valor" title="${escapeHtml(operadoraFull)}">${escapeHtml(operadoraCorta)}</span>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(res => res.json())
    .then(json => {
        renderTable(json.data, json.total);
        renderCards(json.data, json.total);
    })
    .catch(() => {
        const tbody = document.querySelector('#tabla-servicios tbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error cargando servicios.</td></tr>`;

        document.getElementById('cards-servicios').innerHTML =
            `<div class="text-center text-danger py-3">Error cargando servicios.</div>`;
    });
});
</script>
@endsection