@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="text-center mb-4">PANEL DE CONTROL DE CONDUCTORES</h2>

    <div class="mb-4 d-flex">
        <input type="text" id="movil" class="form-control me-2" placeholder="BUSCAR POR MÓVIL">
        <button id="btnBuscar" class="btn btn-dark">Buscar</button>
    </div>

    <div id="tablaResultados"></div>
</div>

{{-- SweetAlert2 y jQuery --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{{-- Setup de CSRF --}}
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });
</script>

<script>
    // Cargar tabla de resultados
    function cargarTabla(movil = '') {
        $.ajax({
            url: '{{ route("conductores.buscarAjax") }}',
            type: 'GET',
            data: { movil: movil },
            success: function(data) {
                let html = '';

                if (data.length === 0) {
                    html = '<div class="alert alert-warning text-center">No se encontraron registros.</div>';
                } else {
                    html += `<table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>MÓVIL</th>
                                <th>CONDUCTOR</th>
                                <th>ESTADO</th>
                                <th>ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    data.forEach(item => {
                        const activo = item.mo_estado == 1;
                        const estadoTexto = activo ? 'ACTIVO' : 'INACTIVO';
                        const estadoColor = activo ? 'text-success fw-bold' : 'text-secondary';
                        const filaColor = activo ? 'table-success' : 'table-light';

                        const boton = activo
                            ? `<button class="btn btn-sm btn-danger" onclick="cambiarEstado(${item.mo_id}, 'desactivar')">Inactivar</button>`
                            : `<button class="btn btn-sm btn-success" onclick="cambiarEstado(${item.mo_id}, 'activar')">Activar</button>`;

                        html += `<tr class="${filaColor}">
                            <td>${item.mo_taxi}</td>
                            <td>${item.conduc_nombres}</td>
                            <td class="${estadoColor}">${estadoTexto}</td>
                            <td>${boton}</td>
                        </tr>`;
                    });

                    html += `</tbody></table>`;
                }

                $('#tablaResultados').html(html);
            },
            error: function() {
                Swal.fire('Error', 'No se pudo cargar la tabla.', 'error');
            }
        });
    }

    // Cambiar estado (activar o desactivar)
    function cambiarEstado(id, accion) {
        $.ajax({
            url: `/conductores/movil/${id}/estado`,
            type: 'POST',
            data: { accion: accion },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: `Registro ${accion} correctamente`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    const movil = $('#movil').val();
                    cargarTabla(movil);
                } else {
                    Swal.fire('Error', 'No se pudo cambiar el estado.', 'error');
                }
            },
            error: function() {
                console.error('ERROR AJAX:', xhr.status, xhr.responseText);
                Swal.fire('Error', 'Fallo al procesar la solicitud.', 'error');
            }
        });
    }

    $(document).ready(function() {
        cargarTabla(); // carga todos los registros al inicio

        $('#btnBuscar').click(function() {
            const movil = $('#movil').val();
            cargarTabla(movil);
        });

        $('#movil').on('keypress', function(e) {
            if (e.which == 13) {
                const movil = $('#movil').val();
                cargarTabla(movil);
            }
        });
    });
</script>
@endsection
