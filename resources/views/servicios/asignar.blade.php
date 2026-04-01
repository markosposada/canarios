@extends('layouts.app')

@section('content')

{{-- ✅ OJO: me dijiste que el CSS lo pondrás en el layout.
     Aquí NO lo incluyo para que quede limpio. --}}

<style>
  /* Cards de pendientes (móvil) */
  .pend-card{
    border:1px solid #e9ecef;
    border-radius:14px;
    padding:12px;
    background:#fff;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    margin-bottom:10px;
  }
  .pend-top{
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:flex-start;
  }
  .pend-num{
    font-weight:800;
    font-size:14px;
    background:#f1f3f5;
    border-radius:10px;
    padding:4px 8px;
    min-width:34px;
    text-align:center;
  }
  .pend-user{
    font-weight:700;
    font-size:14px;
    line-height:1.2;
  }
  .pend-dir{
    font-size:13px;
    color:#495057;
    margin-top:4px;
    line-height:1.25;
    word-break: break-word;
  }
  .pend-meta{
    margin-top:8px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
  }
  .pend-actions{
    display:flex;
    gap:8px;
  }
  .pend-actions .btn{
    padding:8px 10px;
    font-size:13px;
  }
</style>



<div class="container page-wrap">
    <h2 class="text-center page-title">ASIGNA SERVICIOS</h2>

    {{-- Formulario vertical: Usuario -> Dirección --}}
    <div class="row justify-content-center">
        {{-- ✅ En móvil usa todo el ancho (px-0), en desktop vuelve a padding --}}
        <div class="col-12 col-md-10 col-lg-8 px-0 px-md-2">

            {{-- Dirección --}}
<div class="mb-3">
    <label class="form-label">Dirección</label>

    <div class="d-flex align-items-stretch gap-2">
        <input type="text"
               id="inpDireccion"
               class="form-control"
               placeholder="Calle, No, etc."
               inputmode="text"
               autofocus>

        <button type="button"
                class="btn btn-outline-secondary btn-sm icon-btn"
                id="btnDictarDireccion"
                title="Dictar dirección">
            🎤
        </button>

        <button type="button"
                class="btn btn-outline-secondary btn-sm icon-btn"
                id="btnGrabarDireccion"
                title="Grabar audio de la dirección">
            ⏺️
        </button>
    </div>

    <input type="hidden" id="direccionAudioPath">

    <small class="text-muted help-tip d-block mt-1">
        Tip: usa 🎤 para dictar o ⏺️ para grabar audio (no al mismo tiempo).
    </small>
</div>

{{-- Usuario --}}
<div class="mb-3">
    <label class="form-label">Cliente / Usuario</label>

    <div class="d-flex align-items-stretch gap-2">
        <input type="text" id="inpUsuario" class="form-control" placeholder="Nombre del cliente">

        <button type="button"
                class="btn btn-outline-secondary btn-sm icon-btn"
                id="btnMicUsuario"
                title="Dictar usuario">
            🎤
        </button>
    </div>
</div>

            {{-- Botones --}}
            <div class="mt-3 d-flex justify-content-between gap-2">
                <button id="btnAgregarServicio" class="btn btn-primary btn-action">
                    ➕ Agregar
                </button>

                <button id="btnLimpiar" class="btn btn-outline-secondary btn-action" type="button">
                    🧹 Limpiar
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Lista de pendientes --}}
<div class="container pb-3">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8 px-0 px-md-2">

      <div class="card mt-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Servicios pendientes</h5>
            <span class="badge bg-secondary" id="badgePendientes">0</span>
          </div>

          {{-- ✅ MÓVIL: Cards --}}
          <div id="pendientesCards" class="d-block d-md-none mt-3">
            <div class="text-muted text-center py-3">No hay servicios pendientes.</div>
          </div>

          {{-- ✅ TABLET/DESKTOP: Tabla --}}
          <div class="table-responsive mt-2 d-none d-md-block">
            <table class="table table-sm align-middle" id="tablaPendientes">
              <thead>
                <tr>
                  <th style="width:50px">#</th>
                  <th>Usuario</th>
                  <th>Dirección</th>
                  <th style="width:70px" class="text-center">Audio</th>
                  <th style="width:150px" class="text-end">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="5" class="text-muted text-center">
                    No hay servicios pendientes.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <small class="text-muted d-block mt-2">
            Tip: agrega varios servicios y luego asigna uno por uno.
          </small>
        </div>
      </div>

    </div>
  </div>
</div>


{{-- Modal: Lista de móviles activos --}}
<div class="modal fade" id="modalMoviles" tabindex="-1" aria-hidden="true">
  {{-- ✅ Quité modal-lg para que en móvil no sea tan “gigante” --}}
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header flex-column align-items-stretch">
        <div class="w-100 d-flex justify-content-between align-items-center">
            <h5 class="modal-title mb-0">Seleccione el móvil</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
  <span aria-hidden="true">&times;</span>
</button>

        </div>

        <div class="alert alert-primary text-center py-2 mb-2 mt-3 fw-bold">
          📍 <span id="modalDireccionServicio">Dirección no definida</span>
        </div>

        <input type="number"
               id="inpBuscarMovil"
               class="form-control"
               placeholder="Buscar por móvil">
      </div>

      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center" id="tablaMoviles">
                <thead class="table-dark">
                    <tr>
                        <th>ACCIÓN</th>
    <th>MÓVIL</th>
    <th>CANTIDAD</th>
    <th>CONDUCTOR</th>
    <th>PLACA</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
      </div>

      <div class="modal-footer">
        <small class="text-muted">Ordenado por menor cantidad de servicios.</small>
      </div>
    </div>
  </div>
</div>

{{-- Librerías --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ======= Setup y Configuración CSRF ======= */
$.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

const csrfToken = $('meta[name="csrf-token"]').attr('content');
if (!csrfToken) {
    console.error('❌ Token CSRF no encontrado');
    Swal.fire({
        icon: 'error',
        title: 'Error de configuración',
        text: 'No se encontró el token de seguridad. Por favor recargue la página.',
        confirmButtonText: 'Recargar',
        allowOutsideClick: false
    }).then(() => location.reload());
}

// Operadora
@auth
  const OPERADORA = @json(Auth::user()->name ?? 'Operadora');
@else
  const OPERADORA = 'No autenticado';
@endauth

const URL_CONSULTA_TOKEN_BASE = "{{ url('/servicios/consulta') }}?token=";

/* =========================================================
   Copiar al portapapeles (con fallback)
   ========================================================= */
async function copiarAlPortapapeles(texto) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(texto);
      return true;
    }

    const ta = document.createElement('textarea');
    ta.value = texto;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    ta.style.top = '0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(ta);
    return ok;
  } catch (e) {
    console.error('Error copiando:', e);
    return false;
  }
}

/* =========================================================
   1) SpeechRecognition (Usuario)
   ========================================================= */
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

function attachMicSimple(buttonId, inputId, lang = 'es-CO') {
    const btn = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    if (!btn || !input) return;

    if (!SpeechRecognition) {
        btn.disabled = true;
        btn.title = 'Tu navegador no soporta dictado por voz';
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = lang;
    recognition.interimResults = false;
    recognition.continuous = false;

    let listening = false;

    recognition.onstart = () => {
        listening = true;
        btn.textContent = '🛑';
        btn.classList.add('active');
    };

    recognition.onend = () => {
        listening = false;
        btn.textContent = '🎤';
        btn.classList.remove('active');
    };

    recognition.onerror = (e) => {
        console.error('Speech error:', e);
        let msg = 'No se pudo usar el dictado por voz.';
        if (e.error === 'network') msg = 'Falló el dictado (network). Prueba Chrome/Edge.';
        if (e.error === 'not-allowed') msg = 'Permiso de micrófono bloqueado. Revisa el candado del navegador.';
        Swal.fire('Dictado por voz', msg, 'warning');
    };

    recognition.onresult = (event) => {
        const txt = (event.results?.[0]?.[0]?.transcript || '').trim();
        if (!txt) return;
        const current = (input.value || '').trim();
        input.value = current ? (current + ' ' + txt) : txt;
    };

    btn.addEventListener('click', () => {
        try { listening ? recognition.stop() : recognition.start(); } catch (err) { console.error(err); }
    });

    return recognition;
}

attachMicSimple('btnMicUsuario', 'inpUsuario', 'es-CO');

/* =========================================================
   2) Dirección: DOS BOTONES (🎤 dictado) + (⏺️ grabación)
   ========================================================= */
let dir_dict_listening = false;
let dir_dict_recognition = null;

let dir_rec_isRecording = false;
let dir_mediaRecorder = null;
let dir_stream = null;
let dir_audioChunks = [];
let dir_pendingBlob = null;
let dir_pendingUrl = null;

function joinText(a, b) {
  a = (a || '').trim();
  b = (b || '').trim();
  if (!a) return b;
  if (!b) return a;
  return `${a} ${b}`;
}

function resetPendingAudio() {
  dir_pendingBlob = null;
  if (dir_pendingUrl) {
    URL.revokeObjectURL(dir_pendingUrl);
    dir_pendingUrl = null;
  }
}

async function subirAudioDireccion(blob) {
  const form = new FormData();
  form.append('audio', blob, 'direccion.webm');
  form.append('_token', csrfToken);

  const resp = await fetch('{{ route("servicios.audio") }}', { method: 'POST', body: form });

  const contentType = resp.headers.get('content-type') || '';
  const raw = await resp.text();

  if (!contentType.includes('application/json')) {
    console.error('Respuesta NO JSON:', raw);
    throw new Error('Respuesta no JSON del servidor (revisa ruta/logs).');
  }

  const data = JSON.parse(raw);
  if (!resp.ok || !data.success) throw new Error(data.message || 'Upload failed');

  $('#direccionAudioPath').val(data.path || '');
  return data;
}

/* ---- (A) Dictado Dirección ---- */
function initDictadoDireccion() {
  const btn = document.getElementById('btnDictarDireccion');
  const input = document.getElementById('inpDireccion');

  if (!btn || !input) return;

  if (!SpeechRecognition) {
    btn.disabled = true;
    btn.title = 'Tu navegador no soporta dictado por voz';
    return;
  }

  dir_dict_recognition = new SpeechRecognition();
  dir_dict_recognition.lang = 'es-CO';
  dir_dict_recognition.interimResults = false;
  dir_dict_recognition.continuous = false;

  dir_dict_recognition.onstart = () => {
    dir_dict_listening = true;
    btn.textContent = '🛑';
    btn.classList.add('active');
  };

  dir_dict_recognition.onend = () => {
    dir_dict_listening = false;
    btn.textContent = '🎤';
    btn.classList.remove('active');
  };

  dir_dict_recognition.onerror = (e) => {
    console.error('Dictado Dirección error:', e);
    let msg = 'No se pudo dictar la dirección.';
    if (e.error === 'not-allowed') msg = 'Permiso de micrófono bloqueado.';
    if (e.error === 'network') msg = 'Falló el dictado (network).';
    Swal.fire('Dictado', msg, 'warning');
  };

  dir_dict_recognition.onresult = (event) => {
    const txt = (event.results?.[0]?.[0]?.transcript || '').trim();
    if (!txt) return;
    input.value = joinText(input.value, txt);
  };

  btn.addEventListener('click', () => {
    if (dir_rec_isRecording) {
      Swal.fire('Grabación en curso', 'Detén la grabación ⏺️ antes de dictar 🎤.', 'warning');
      return;
    }
    input.focus();
    try { dir_dict_listening ? dir_dict_recognition.stop() : dir_dict_recognition.start(); }
    catch (err) { console.error(err); }
  });
}

/* ---- (B) Grabación Dirección ---- */
async function startRecordingDireccion() {
  if (!navigator.mediaDevices?.getUserMedia) throw new Error('Tu navegador no soporta grabación de audio.');

  dir_stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  dir_audioChunks = [];

  let options = {};
  if (MediaRecorder.isTypeSupported?.('audio/webm;codecs=opus')) options = { mimeType: 'audio/webm;codecs=opus' };
  else if (MediaRecorder.isTypeSupported?.('audio/webm')) options = { mimeType: 'audio/webm' };

  dir_mediaRecorder = new MediaRecorder(dir_stream, options);

  dir_mediaRecorder.ondataavailable = (e) => {
    if (e.data && e.data.size > 0) dir_audioChunks.push(e.data);
  };

  dir_mediaRecorder.onstop = async () => {
    try { dir_stream.getTracks().forEach(t => t.stop()); } catch (_) {}

    dir_pendingBlob = new Blob(dir_audioChunks, { type: dir_mediaRecorder.mimeType || 'audio/webm' });
    dir_pendingUrl = URL.createObjectURL(dir_pendingBlob);

    const result = await Swal.fire({
      icon: 'info',
      title: 'Audio de dirección listo',
      html: `
        <div style="text-align:left;font-size:13px;margin-bottom:8px;">
          Escúchalo antes de guardarlo:
        </div>
        <audio controls style="width:100%;">
          <source src="${dir_pendingUrl}" type="${dir_mediaRecorder.mimeType || 'audio/webm'}">
        </audio>
        <div style="margin-top:10px;font-size:12px;color:#666;">
          - <b>Guardar</b>: sube el audio y lo asocia al servicio.<br>
          - <b>Regrabar</b>: vuelve a grabar desde cero.<br>
          - <b>Descartar</b>: no se guarda audio.
        </div>
      `,
      showCancelButton: true,
      showDenyButton: true,
      confirmButtonText: 'Guardar',
      denyButtonText: 'Regrabar',
      cancelButtonText: 'Descartar',
      allowOutsideClick: false
    });

    if (result.isConfirmed) {
      try {
        Swal.fire({ title: 'Subiendo audio...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        await subirAudioDireccion(dir_pendingBlob);
        resetPendingAudio();
        Swal.fire('OK', 'Audio guardado ✅', 'success');
      } catch (e) {
        console.error(e);
        $('#direccionAudioPath').val('');
        resetPendingAudio();
        Swal.fire('Error', 'No se pudo subir el audio. Revisa el backend.', 'error');
      }
    } else if (result.isDenied) {
      $('#direccionAudioPath').val('');
      resetPendingAudio();
      toggleGrabacionDireccion(true).catch(err => {
        console.error(err);
        Swal.fire('Error', 'No se pudo iniciar la regrabación.', 'error');
      });
    } else {
      $('#direccionAudioPath').val('');
      resetPendingAudio();
    }
  };

  dir_mediaRecorder.start();
}

async function stopRecordingDireccion() {
  try { dir_mediaRecorder?.stop(); } catch (_) {}
  try { dir_stream?.getTracks()?.forEach(t => t.stop()); } catch (_) {}
}

async function toggleGrabacionDireccion(forceStart = false) {
  const btn = document.getElementById('btnGrabarDireccion');

  if (!dir_rec_isRecording || forceStart) {
    if (dir_dict_listening) {
      Swal.fire('Dictado en curso', 'Detén el dictado 🎤 antes de grabar ⏺️.', 'warning');
      return;
    }

    $('#direccionAudioPath').val('');
    resetPendingAudio();

    dir_rec_isRecording = true;
    btn.textContent = '🛑';
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-danger');

    await startRecordingDireccion();
    return;
  }

  dir_rec_isRecording = false;
  btn.textContent = '⏺️';
  btn.classList.add('btn-outline-secondary');
  btn.classList.remove('btn-danger');

  await stopRecordingDireccion();
}

document.getElementById('btnGrabarDireccion')?.addEventListener('click', () => {
  toggleGrabacionDireccion().catch(err => {
    console.error(err);
    Swal.fire('Error', err?.message || 'No se pudo iniciar/detener la grabación.', 'error');
  });
});

initDictadoDireccion();

/* =========================================================
   3) MÓVILES (modal)
   ========================================================= */
/* =========================================================
   3) MÓVILES (modal) - FIX REAL
   - Usa $.ajax con dataType json
   - Si el backend devuelve HTML, cae en error y lo vemos
   ========================================================= */

// ✅ instancia única del modal (Bootstrap 5)
let modalMovilesInstance = null;
function getModalMoviles() {
  const el = document.getElementById('modalMoviles');
  if (!el) return null;
  modalMovilesInstance = bootstrap.Modal.getOrCreateInstance(el);
  return modalMovilesInstance;
}

function abrirModalMoviles() {
  $('#modalMoviles').modal('show');

  $('#modalMoviles').on('shown.bs.modal', function () {
    $('#inpBuscarMovil').focus();
});
}

function cerrarModalMoviles() {
  $('#modalMoviles').modal('hide');
}


// ✅ carga móviles garantizando JSON
function cargarMoviles(q = '') {
  return $.ajax({
    url: '{{ route("servicios.moviles") }}',
    method: 'GET',
    data: { q },
    dataType: 'json', // 🔥 CLAVE: obliga JSON
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  });
}

function pintarTabla(moviles) {
  const $tbody = $('#tablaMoviles tbody').empty();

  if (!Array.isArray(moviles)) {
    console.error('❌ moviles NO es array:', moviles);
    $tbody.append('<tr><td colspan="5" class="text-danger">Respuesta inválida del servidor.</td></tr>');
    return;
  }

  if (!moviles.length) {
    $tbody.append('<tr><td colspan="5" class="text-muted">No hay móviles activos.</td></tr>');
    return;
  }

  moviles.forEach(m => {
    $tbody.append(`
      <tr>
        <td><button class="btn btn-sm btn-success" onclick="asignar(${m.mo_id})">Disponible</button></td>
        <td>${m.mo_taxi ?? ''}</td>
        <td>${m.cantidad ?? 0}</td>
        <td>${m.nombre_conductor ?? ''}</td>
        <td>${m.placa ?? ''}</td>
      </tr>
    `);
  });
}

function debounce(fn, wait = 200) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
}

const filtrarMoviles = debounce(async function () {
  const q = $('#inpBuscarMovil').val();
  try {
    $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-muted">Buscando...</td></tr>');
    const moviles = await cargarMoviles(q);
    pintarTabla(moviles);
  } catch (e) {
    console.error('❌ Error cargando móviles:', e);

    // 419 muy común por sesión/CSRF expirada
    if (e?.status === 419) {
      Swal.fire({
        icon: 'warning',
        title: 'Sesión expirada',
        text: 'Tu sesión expiró. Se recargará la página.',
        confirmButtonText: 'Recargar',
        allowOutsideClick: false
      }).then(() => location.reload());
      return;
    }

    // Si el servidor devolvió HTML, jquery marca parsererror
    let msg = 'No fue posible cargar los móviles.';
    if (e?.status === 200 && e?.responseText) {
      msg = 'El servidor devolvió HTML y no JSON (posible login/redirección/error).';
      console.error('Preview HTML:', String(e.responseText).slice(0, 400));
    } else if (e?.status) {
      msg = `Error HTTP ${e.status}. Revisa logs del servidor.`;
    } else if (e?.statusText) {
      msg = `Error: ${e.statusText}`;
    }

    $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-danger">Error al cargar móviles</td></tr>');
    Swal.fire('Error', msg, 'error');
  }
}, 250);

$('#inpBuscarMovil').off('input').on('input', filtrarMoviles);
$('#inpBuscarMovil').off('keydown').on('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });


/* =========================================================
   4) PENDIENTES (carrito)
   ========================================================= */
let pendientes = [];
let idxSeleccionado = null;
const LS_KEY = 'canarios_pendientes';

function savePendientes() { localStorage.setItem(LS_KEY, JSON.stringify(pendientes)); }

function loadPendientes() {
  try {
    const raw = localStorage.getItem(LS_KEY);
    if (!raw) return;
    pendientes = JSON.parse(raw) || [];
  } catch (_) { pendientes = []; }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function resetFormServicio() {
  $('#inpUsuario').val('');
  $('#inpDireccion').val('');
  $('#direccionAudioPath').val('');
  resetPendingAudio();
  $('#inpDireccion').focus();
}

function renderPendientes() {
  // badge
  $('#badgePendientes').text(pendientes.length);

  // ====== (A) TABLET/DESKTOP: tabla ======
  const $tbody = $('#tablaPendientes tbody').empty();

  if (!pendientes.length) {
    $tbody.append(`<tr><td colspan="5" class="text-muted text-center">No hay servicios pendientes.</td></tr>`);
  } else {
    pendientes.forEach((p, i) => {
      const audioTxt = p.audio_path ? '✅' : '—';
      $tbody.append(`
        <tr>
          <td>${i + 1}</td>
          <td>${escapeHtml(p.usuario)}</td>
          <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            ${escapeHtml(p.direccion)}
          </td>
          <td class="text-center">${audioTxt}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-success me-2" onclick="seleccionarPendiente(${i})">Asignar</button>
            <button class="btn btn-sm btn-outline-danger" onclick="quitarPendiente(${i})">Quitar</button>
          </td>
        </tr>
      `);
    });
  }

  // ====== (B) MÓVIL: cards ======
  const $cards = $('#pendientesCards').empty();

  if (!pendientes.length) {
    $cards.html(`<div class="text-muted text-center py-3">No hay servicios pendientes.</div>`);
    return;
  }

  pendientes.forEach((p, i) => {
    const audioTxt = p.audio_path ? '✅ Audio' : '— Sin audio';

    $cards.append(`
      <div class="pend-card">
        <div class="pend-top">
          <div class="pend-num">${i + 1}</div>
          <div class="flex-grow-1">
            <div class="pend-user">${escapeHtml(p.usuario)}</div>
            <div class="pend-dir">${escapeHtml(p.direccion)}</div>
          </div>
        </div>

        <div class="pend-meta">
          <span class="badge bg-light text-dark" style="border:1px solid #e9ecef">${audioTxt}</span>

          <div class="pend-actions">
            <button class="btn btn-success btn-sm" onclick="seleccionarPendiente(${i})">Asignar</button>
            <button class="btn btn-outline-danger btn-sm" onclick="quitarPendiente(${i})">Quitar</button>
          </div>
        </div>
      </div>
    `);
  });
}


function quitarPendiente(i) { pendientes.splice(i, 1); savePendientes(); renderPendientes(); }

window.seleccionarPendiente = async function(i) {
  if (dir_rec_isRecording || dir_dict_listening) {
    Swal.fire('Acción en curso', 'Detén dictado/grabación antes de asignar.', 'warning');
    return;
  }

  if (!OPERADORA || OPERADORA === 'No autenticado') {
    Swal.fire('Error', 'No se pudo identificar la operadora. Inicie sesión nuevamente.', 'error');
    return;
  }

  idxSeleccionado = i;
  $('#modalDireccionServicio').text(pendientes[i]?.direccion || 'Dirección no definida');

  try {
    const moviles = await cargarMoviles('');
    pintarTabla(moviles);

    abrirModalMoviles();


    
    $('#inpBuscarMovil').val('');
  } catch (e) {
    console.error('Error cargando móviles:', e);
    Swal.fire('Error', 'No fue posible cargar los móviles.', 'error');
  }
}

/* Agregar */
$('#btnAgregarServicio').on('click', () => {
  const usuario = $('#inpUsuario').val().trim();
  const direccion = $('#inpDireccion').val().trim();
  const audio_path = $('#direccionAudioPath').val().trim();

  if (!usuario || !direccion) {
    Swal.fire('Faltan datos', 'Usuario y Dirección son obligatorios.', 'warning');
    return;
  }

  if (dir_rec_isRecording || dir_dict_listening) {
    Swal.fire('Acción en curso', 'Detén dictado/grabación antes de agregar.', 'warning');
    return;
  }

  pendientes.push({ usuario, direccion, audio_path });
  savePendientes();
  renderPendientes();
  resetFormServicio();

  Swal.fire({ icon:'success', title:'Agregado', text:'Servicio agregado a pendientes.', timer:800, showConfirmButton:false });
});

/* Limpiar */
$('#btnLimpiar').on('click', () => {
  if (dir_rec_isRecording || dir_dict_listening) {
    Swal.fire('Acción en curso', 'Detén dictado/grabación antes de limpiar.', 'warning');
    return;
  }
  $('#inpUsuario, #inpDireccion, #direccionAudioPath').val('');
  resetPendingAudio();
  $('#inpDireccion').focus();
});

/* =========================================================
   5) ASIGNAR
   ========================================================= */
async function asignar(mo_id) {
  if (idxSeleccionado === null || typeof pendientes[idxSeleccionado] === 'undefined') {
    Swal.fire('Error', 'No hay servicio seleccionado para asignar.', 'error');
    return;
  }

  const s = pendientes[idxSeleccionado];

  try {
    const resp = await $.ajax({
      url: '{{ route("servicios.registrar") }}',
      method: 'POST',
      data: {
        _token: csrfToken,
        conmo: mo_id,
        usuario: s.usuario,
        direccion: s.direccion,
        operadora: OPERADORA,
        audio_path: s.audio_path || ''
      },
      dataType: 'json'
    });

    const token = resp.token || '---';
    const movil = resp.movil || '---';
    const placa = resp.placa || '---';


    pendientes.splice(idxSeleccionado, 1);
    idxSeleccionado = null;
    savePendientes();
    renderPendientes();

    cerrarModalMoviles();


      Swal.fire({
        icon: 'success',
        title: 'Servicio asignado',
        html: `<div style="font-size:14px">
          Móvil: <strong>${movil}</strong><br>
          Placa: <strong>${placa}</strong><br><br>
          <div style="font-size:14px">Código para consulta (24h):</div>
          <div style="font-size:32px;font-weight:800;letter-spacing:3px">${token}</div>
        </div>`,
        confirmButtonText: 'OK',
      }).then(async (r) => {
        if (!r.isConfirmed) return;

        const urlConsulta = URL_CONSULTA_TOKEN_BASE + encodeURIComponent(token);

        const texto = [
          '🚖 Su taxi va en camino.',
          'La información del servicio es la siguiente:',
          `🚕 Móvil: ${movil}`,
          `🔖 Placa: ${placa}`,
          `Puede ampliar la información ingresando en el siguiente enlace🔗: ${urlConsulta}`
        ].join('\n');

        const ok = await copiarAlPortapapeles(texto);

        if (ok) {
          Swal.fire({ icon:'success', title:'Copiado', text:'La información fue copiada ✅', timer:800, showConfirmButton:false });
        } else {
          Swal.fire({ icon:'warning', title:'No se pudo copiar', text:'El navegador bloqueó el portapapeles. Copia manualmente.' });
        }
      });



  } catch (error) {
    console.error('❌ Error completo:', error);

    if (error.status === 419) {
      Swal.fire({
        icon: 'warning',
        title: 'Sesión expirada',
        text: 'Tu sesión expiró. Se recargará la página.',
        confirmButtonText: 'Recargar',
        allowOutsideClick: false
      }).then(() => location.reload());
      return;
    }

    let errorMsg = 'No se pudo registrar el servicio.';
    if (error.responseJSON?.errors) {
      errorMsg = Object.values(error.responseJSON.errors).flat().join('<br>');
    } else if (error.responseJSON?.message) {
      errorMsg = error.responseJSON.message;
    }

    Swal.fire({ icon: 'error', title: 'Error', html: errorMsg });
  }
}

/* =========================================================
   6) Navegación Enter
   ========================================================= */
$('#inpDireccion').on('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    $('#inpUsuario').focus();
  }
});

$('#inpUsuario').on('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    $('#btnAgregarServicio').click();
  }
});

/* =========================================================
   INIT
   ========================================================= */
loadPendientes();
renderPendientes();
</script>
@endsection
