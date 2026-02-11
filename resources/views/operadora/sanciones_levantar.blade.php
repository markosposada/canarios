@extends('layouts.app')
@section('title','Anular / Levantar sanciones')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">ANULAR / LEVANTAR SANCIONES</h3>
    <p class="text-muted mb-0">Aquí no se borra nada: se marca como levantada y se guarda el motivo.</p>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body p-2 p-md-3">

        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-2">
          <h4 class="card-title mb-0 fs-6">Sanciones Recientes</h4>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-primary px-2 py-1" id="btnRefrescar" type="button">
              <i class="mdi mdi-refresh"></i> Refrescar
            </button>
            <a href="{{ route('operadora.sancionar') }}" class="btn btn-sm btn-outline-secondary px-2 py-1">
              Ir a Sancionar
            </a>
          </div>
        </div>

        <div class="table-responsive mt-2">
          <table class="table table-hover table-sm tabla-sanciones" id="tablaSanciones">
            <thead class="table-light">
              <tr>
                <th style="min-width:50px;">Móvil</th>
                <th style="min-width:140px;">Conductor</th>
                <th style="min-width:110px;">Tipo</th>
                <th style="min-width:100px;">Fecha</th>
                <th style="min-width:65px;">Oper.</th>
                <th style="min-width:120px;">Estado</th>
                <th style="min-width:85px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="7" class="text-muted text-center py-3">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <style>
          /* Estilos de badges optimizados */
          .badge-soft{ 
            padding:.2rem .4rem; 
            border-radius:12px; 
            font-weight:600; 
            font-size:10px; 
            display:inline-block;
            white-space:nowrap;
            line-height:1.2;
          }
          .badge-vigente{ 
            background:#d1fae5; 
            color:#065f46; 
            border:1px solid #10b981; 
          }
          .badge-vencida{ 
            background:#fee2e2; 
            color:#991b1b; 
            border:1px solid #ef4444; 
          }
          .badge-levantada{ 
            background:#e5e7eb; 
            color:#111827; 
            border:1px solid #9ca3af; 
          }
          
          .small-muted{ 
            font-size:10px; 
            color:#6c757d; 
            line-height:1.2;
            margin-top:1px;
            display:block;
          }

          /* Optimización global de la tabla */
          .tabla-sanciones {
            font-size: 12px;
            margin-bottom: 0;
          }

          .tabla-sanciones td {
            padding: 0.4rem 0.3rem;
            vertical-align: middle;
            border-color: #e9ecef;
          }

          .tabla-sanciones th {
            padding: 0.5rem 0.3rem;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            background-color: #f8f9fa;
            border-color: #dee2e6;
          }

          .tabla-sanciones tbody tr:hover {
            background-color: #f8f9fa;
          }

          /* Contenedor responsive mejorado */
          .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -8px;
            padding: 0 8px;
          }

          /* Ajustes para botones */
          .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 11px;
            line-height: 1.3;
          }

          /* Texto conductor compacto */
          .conductor-nombre {
            font-weight: 500;
            margin-bottom: 1px;
            line-height: 1.2;
          }

          /* Botón deshabilitado con tooltip */
          .btn-disabled-tooltip {
            cursor: not-allowed;
            opacity: 0.5;
          }

          /* Responsive para tablets */
          @media (max-width: 991px) {
            .card-body {
              padding: 0.75rem !important;
            }

            .tabla-sanciones {
              font-size: 11px;
            }

            .tabla-sanciones th {
              font-size: 10px;
              padding: 0.4rem 0.25rem;
            }

            .tabla-sanciones td {
              padding: 0.35rem 0.25rem;
            }

            .badge-soft {
              font-size: 9px;
              padding: 0.15rem 0.35rem;
            }

            .small-muted {
              font-size: 9px;
            }

            .btn-sm {
              font-size: 10px;
              padding: 0.15rem 0.35rem;
            }
          }

          /* Responsive para móviles */
          @media (max-width: 576px) {
            .tabla-sanciones {
              font-size: 10px;
            }

            h3.mb-1 {
              font-size: 1.2rem;
            }

            .fs-6 {
              font-size: 0.9rem !important;
            }
          }

          /* Mejorar el scroll horizontal en touch devices */
          @media (hover: none) {
            .table-responsive {
              scrollbar-width: thin;
            }
            
            .table-responsive::-webkit-scrollbar {
              height: 6px;
            }
            
            .table-responsive::-webkit-scrollbar-thumb {
              background-color: rgba(0,0,0,0.2);
              border-radius: 3px;
            }
          }
        </style>

      </div>
    </div>
  </div>
</div>

{{-- Modal: Levantar sanción --}}
<div class="modal fade" id="modalLevantar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Levantar sanción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2 px-3">
          Esta acción <strong>no elimina</strong> la sanción, solo la marca como levantada y guarda el motivo.
        </div>

        <input type="hidden" id="levantarId" value="">

        <div class="mb-2">
          <label class="form-label fw-bold mb-1">Motivo (obligatorio)</label>
          <textarea class="form-control form-control-sm" id="levantarMotivo" rows="3" maxlength="255"
                    placeholder="Ej: Se verificó y fue un error / Se solucionó el problema"></textarea>
          <div class="small-muted mt-1">Máximo 255 caracteres.</div>
        </div>

        <div id="msgLevantar"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-sm btn-success" id="btnConfirmLevantar">Levantar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlListar = "{{ route('operadora.sanciones.listar') }}";

  const btnRefrescar = document.getElementById('btnRefrescar');

  const modalEl = document.getElementById('modalLevantar');
  const levantarId = document.getElementById('levantarId');
  const levantarMotivo = document.getElementById('levantarMotivo');
  const msgLevantar = document.getElementById('msgLevantar');
  const btnConfirmLevantar = document.getElementById('btnConfirmLevantar');

  let bsModal = null;

  function escapeHtml(str){
    if (str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function showMsgLevantar(html, cls='alert alert-info') {
    msgLevantar.innerHTML = `<div class="${cls} py-2">${html}</div>`;
    setTimeout(()=>{ msgLevantar.innerHTML=''; }, 3500);
  }

  // Función para verificar si han pasado más de 24 horas desde la sanción
  function hasPasado24Horas(fecha, hora) {
    if (!fecha || !hora) return false;
    
    try {
      // Asumiendo formato YYYY-MM-DD para fecha y HH:MM:SS para hora
      const fechaHoraSancion = new Date(fecha + ' ' + hora);
      const ahora = new Date();
      
      // Calcular diferencia en milisegundos
      const diferenciaMilisegundos = ahora - fechaHoraSancion;
      
      // Convertir a horas (1000ms * 60seg * 60min = 1 hora)
      const diferenciaHoras = diferenciaMilisegundos / (1000 * 60 * 60);
      
      return diferenciaHoras > 24;
    } catch (e) {
      console.error('Error al calcular tiempo transcurrido:', e);
      return false;
    }
  }

  function badgeEstado(r) {
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    const vigente = parseInt(r.vigente ?? 0) === 1;

    if (!activa) {
      const extra = (r.sancion_levantada_fecha || r.sancion_levantada_hora)
        ? `<div class="small-muted">Lev: ${escapeHtml(r.sancion_levantada_fecha || '')} ${escapeHtml(r.sancion_levantada_hora || '')}</div>
           <div class="small-muted">${escapeHtml(r.sancion_levantada_operadora ? r.sancion_levantada_operadora.substring(0,10) : '')}</div>`
        : '';
      return `<span class="badge-soft badge-levantada">LEVANTADA</span>${extra}`;
    }

    if (vigente) {
      return `<span class="badge-soft badge-vigente">VIGENTE (${escapeHtml(r.minutos_restantes)}m)</span>`;
    }

    return `<span class="badge-soft badge-vencida">VENCIDA</span>`;
  }

  function btnAcciones(r) {
    const activa = parseInt(r.sancion_activa ?? 1) === 1;
    
    // Si ya está levantada, no mostrar botón
    if (!activa) {
      return `<span class="text-muted">—</span>`;
    }
    
    // Verificar si han pasado más de 24 horas
    const paso24h = hasPasado24Horas(r.fecha, r.hora);
    
    if (paso24h) {
      return `<button class="btn btn-sm btn-outline-secondary btn-disabled-tooltip" 
                      disabled 
                      title="No se puede levantar después de 24 horas">
                Expirado
              </button>`;
    }
    
    // Botón normal si está dentro de las 24 horas
    return `<button class="btn btn-sm btn-outline-success btn-levantar" 
                    data-id="${r.sancion_id}">
              Levantar
            </button>`;
  }

  function attachLevantarHandlers() {
    document.querySelectorAll('.btn-levantar').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        if (!id) return;

        levantarId.value = id;
        levantarMotivo.value = '';
        msgLevantar.innerHTML = '';

        if (window.bootstrap && window.bootstrap.Modal) {
          bsModal = bsModal || new bootstrap.Modal(modalEl);
          bsModal.show();
        } else {
          alert('Bootstrap modal no está disponible en este layout.');
        }
      });
    });
  }

  function cargarSanciones() {
    fetch(urlListar, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(json => {
        const rows = json.data || [];
        const tbody = document.querySelector('#tablaSanciones tbody');
        tbody.innerHTML = '';

        if (!rows.length) {
          tbody.innerHTML = `<tr><td colspan="7" class="text-muted text-center py-3">Sin sanciones recientes.</td></tr>`;
          return;
        }

        rows.forEach((r) => {
          const tr = document.createElement('tr');
          const operadoraCorta = (r.operadora || '').substring(0, 6);
          const conductorNombre = escapeHtml(r.conductor || '').split(' ')[0] + ' ' + (escapeHtml(r.conductor || '').split(' ')[1] || '');
          
          tr.innerHTML = `
            <td class="fw-bold">${escapeHtml(r.sancion_movil)}</td>
            <td>
              <div class="conductor-nombre" title="${escapeHtml(r.conductor)}">${conductorNombre}</div>
              <div class="small-muted">CC ${escapeHtml(r.sancion_condu)}</div>
            </td>
            <td>
              <div>${escapeHtml(r.tipo)}</div>
              <div class="small-muted">${escapeHtml(r.horas)}h</div>
              ${r.sancion_levantada_motivo ? `<div class="small-muted" title="${escapeHtml(r.sancion_levantada_motivo)}">Mot: ${escapeHtml(r.sancion_levantada_motivo.substring(0,15))}...</div>` : ''}
            </td>
            <td>
              <div>${escapeHtml(r.fecha)}</div>
              <div class="small-muted">${escapeHtml(r.hora)}</div>
            </td>
            <td title="${escapeHtml(r.operadora || '')}">${escapeHtml(operadoraCorta)}</td>
            <td>${badgeEstado(r)}</td>
            <td>${btnAcciones(r)}</td>
          `;
          tbody.appendChild(tr);
        });

        attachLevantarHandlers();
      })
      .catch(() => {
        const tbody = document.querySelector('#tablaSanciones tbody');
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-3">Error cargando sanciones.</td></tr>`;
      });
  }

  btnRefrescar.addEventListener('click', cargarSanciones);

  btnConfirmLevantar.addEventListener('click', function () {
    const id = levantarId.value;
    const motivo = (levantarMotivo.value || '').trim();

    if (!id) return showMsgLevantar('No se encontró el ID de la sanción.', 'alert alert-danger');
    if (!motivo) return showMsgLevantar('El motivo es obligatorio.', 'alert alert-warning');

    btnConfirmLevantar.disabled = true;

    const urlLevantar = "{{ url('/operadora/sanciones') }}/" + encodeURIComponent(id) + "/levantar";

    fetch(urlLevantar, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ motivo })
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if (!ok || !j.success) throw new Error(j.message || 'No se pudo levantar la sanción.');
      showMsgLevantar('✅ Sanción levantada.', 'alert alert-success');
      if (bsModal) bsModal.hide();
      cargarSanciones();
    })
    .catch(e => showMsgLevantar('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(() => btnConfirmLevantar.disabled = false);
  });

  cargarSanciones();
});
</script>
@endsection
