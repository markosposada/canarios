@extends('layouts.app_conductor')

@section('title', 'Facturación')

@section('content')
<div class="row">
    <div class="col-12">
        <h3 class="mb-3">FACTURACIÓN (ÚLTIMOS 5 DÍAS)</h3>
    </div>
</div>

{{-- Resumen --}}
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Registros</p>
                    <h4 class="mb-0" id="totalRegistros">0</h4>
                </div>
                <i class="mdi mdi-format-list-numbered mdi-36px text-info"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Facturado</p>
                    <h4 class="mb-0" id="totalFacturado">$0</h4>
                </div>
                <i class="mdi mdi-cash-multiple mdi-36px text-warning"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Pagado</p>
                    <h4 class="mb-0" id="totalPagado">$0</h4>
                </div>
                <i class="mdi mdi-check-circle mdi-36px text-success"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Debe</p>
                    <h4 class="mb-0" id="totalDebe">$0</h4>
                </div>
                <i class="mdi mdi-alert-circle mdi-36px text-danger"></i>
            </div>
        </div>
    </div>
</div>

{{-- Tabla --}}
<div class="row">
    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Resumen facturación</h4>

                <div class="table-responsive">
                    <table class="table table-striped" id="tabla-facturacion">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:70px;">Movil</th>
                                <th style="min-width:100px;">Fecha</th>
                                <th style="min-width:80px;">Hora</th>
                                <th style="min-width:110px;">Operadora</th>
                                <th style="min-width:110px;">Total</th>
                                <th style="min-width:110px;">Pago</th>
                                <th style="min-width:110px;">Método</th>
                                <th>Obs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <style>
                    #tabla-facturacion td.col-obs, #tabla-facturacion th.col-obs{
                        max-width: 260px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .table-responsive{ -webkit-overflow-scrolling: touch; }
                </style>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const url = "{{ route('conductor.facturacion.listar') }}";

    function escapeHtml(str){
        if (str === null || str === undefined) return '';
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function money(n){
        n = Number(n || 0);
        // Formato simple (COP). Si quieres separadores tipo 12.345:
        return '$' + n.toLocaleString('es-CO');
    }

    function pagoBadge(v){
        v = Number(v);
        if (v === 1) return `<span class="badge bg-success">PAGADO</span>`;
        return `<span class="badge bg-danger">DEBE</span>`;
    }

    function renderRows(rows, totales){
        const tbody = document.querySelector('#tabla-facturacion tbody');
        tbody.innerHTML = '';

        document.getElementById('totalRegistros').textContent = String(totales?.registros ?? 0);
        document.getElementById('totalFacturado').textContent = money(totales?.facturado ?? 0);
        document.getElementById('totalPagado').textContent = money(totales?.pagado ?? 0);
        document.getElementById('totalDebe').textContent = money(totales?.debe ?? 0);

        if (!rows || rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted">No tienes facturación en los últimos 5 días.</td></tr>`;
            return;
        }

        rows.forEach((r, idx) => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td class="fw-bold">${idx + 1}</td>
                <td>${escapeHtml(r.fo_movil ?? '')}</td>
                <td>${escapeHtml(r.fo_fecha ?? '')}</td>
                <td>${escapeHtml(r.fo_hora ?? '')}</td>
                <td title="${escapeHtml(r.operadora ?? '')}">${escapeHtml(r.operadora ?? '')}</td>
                <td>${money(r.fo_total)}</td>
                <td>${pagoBadge(r.fo_pagado)}</td>
                <td>${escapeHtml(r.fo_metodo ?? '')}</td>
                <td class="col-obs" title="${escapeHtml(r.fo_observacion ?? '')}">
                    ${escapeHtml(r.fo_observacion ?? '')}
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Si DataTables está cargado en tu template, lo activa
        if (window.$ && $.fn.DataTable) {
            if ($.fn.DataTable.isDataTable('#tabla-facturacion')) {
                $('#tabla-facturacion').DataTable().destroy();
            }

            $('#tabla-facturacion').DataTable({
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
    .then(json => renderRows(json.data, json.totales))
    .catch(() => {
        const tbody = document.querySelector('#tabla-facturacion tbody');
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">Error cargando facturación.</td></tr>`;
    });
});
</script>
@endsection
