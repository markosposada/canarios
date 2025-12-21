@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 text-center">ASIGNA SERVICIOS</h2>

    {{-- Formulario vertical: Usuario -> Dirección --}}
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="mb-3">
                <label class="form-label">Cliente / Usuario</label>
                <div class="input-group">
                    <input type="text" id="inpUsuario" class="form-control" placeholder="Nombre del cliente" autofocus>
                    <button type="button" class="btn btn-outline-secondary" id="btnMicUsuario" title="Dictar usuario">🎤</button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Dirección</label>
                <div class="input-group">
                    <input type="text" id="inpDireccion" class="form-control" placeholder="Calle, No, etc.">
                    {{-- Botón único: transcribe en vivo + graba audio --}}
                    <button type="button" class="btn btn-outline-secondary" id="btnVoiceDireccion" title="Grabar y transcribir dirección">
                        🎤⏺️
                    </button>
                </div>
                <input type="hidden" id="direccionAudioPath">
                <div class="mt-1">
                    <small class="text-muted">Tip: 1er clic para iniciar, 2do clic para detener y guardar.</small>
                </div>
            </div>

           <div class="mt-4 d-grid gap-4">
   <button id="btnAbrirModal" class="btn btn-success btn-lg fw-bold">
   🚕 ASIGNAR SERVICIO
</button>

    <button id="btnLimpiar" class="btn btn-outline-secondary">
      🧹 Limpiar
    </button>
</div>


        </div>
    </div>
</div>

{{-- Modal: Lista de móviles activos --}}
<div class="modal fade" id="modalMoviles" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div class="w-100">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="modal-title mb-0">Seleccione el móvil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="mt-3">
                <input type="text" id="inpBuscarMovil" class="form-control" placeholder="Buscar por móvil, conductor o placa...">
            </div>
        </div>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center" id="tablaMoviles">
                <thead class="table-dark">
                    <tr>
                        <th>MÓVIL</th>
                        <th>CONDUCTOR</th>
                        <th>PLACA</th>
                        <th>CANTIDAD (hoy)</th>
                        <th>ACCIÓN</th>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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

/* =========================================================
   1) Mic Usuario (solo transcribe, SIN preview, no duplica)
   ========================================================= */
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

function attachMicSimple(buttonId, inputId, lang = 'es-ES') {
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
    recognition.interimResults = false; // ✅ sin preview
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
        if (e.error === 'network') msg = 'Falló el dictado (network). Prueba Edge/Chrome y localhost.';
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
}

attachMicSimple('btnMicUsuario', 'inpUsuario', 'es-ES');

/* =========================================================
   2) Dirección: UN SOLO botón
      - 1er clic: transcribe en vivo + graba
      - 2do clic: detiene y muestra mini-resumen (escuchar)
      - Guardar => sube audio y guarda audio_path
   ========================================================= */
let dir_isRunning = false;

// Speech
let dir_recognition = null;
let dir_baseText = '';
let dir_finalText = '';

// Record
let dir_mediaRecorder = null;
let dir_stream = null;
let dir_audioChunks = [];

// Pending audio (para mini resumen)
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

function startTranscriptionDireccion() {
  if (!SpeechRecognition) throw new Error('Este navegador no soporta SpeechRecognition.');

  const input = document.getElementById('inpDireccion');
  dir_baseText = (input.value || '').trim();
  dir_finalText = '';

  dir_recognition = new SpeechRecognition();
  dir_recognition.lang = 'es-ES';
  dir_recognition.interimResults = true; // ✅ en vivo
  dir_recognition.continuous = true;     // ✅ sigue hasta stop

  dir_recognition.onresult = (event) => {
    let interim = '';
    let finals = '';

    for (let i = event.resultIndex; i < event.results.length; i++) {
      const txt = event.results[i][0].transcript;
      if (event.results[i].isFinal) finals += txt;
      else interim += txt;
    }

    if (finals) dir_finalText += finals;

    const currentFinal = joinText(dir_baseText, dir_finalText);
    input.value = joinText(currentFinal, interim);
  };

  dir_recognition.onerror = (e) => {
    console.error('Speech error:', e);
    // No rompemos todo, solo avisamos
    let msg = 'Falló la transcripción.';
    if (e.error === 'network') msg = 'Falló la transcripción (network). Prueba Edge/Chrome y localhost.';
    if (e.error === 'not-allowed') msg = 'Permiso de micrófono bloqueado para transcripción.';
    Swal.fire('Transcripción', msg, 'warning');
  };

  dir_recognition.onend = () => {
    // consolidar sin interim
    const input = document.getElementById('inpDireccion');
    input.value = joinText(dir_baseText, dir_finalText).trim();
  };

  dir_recognition.start();
}

async function startRecordingDireccion() {
  if (!navigator.mediaDevices?.getUserMedia) throw new Error('Tu navegador no soporta grabación de audio.');

  dir_stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  dir_audioChunks = [];

  dir_mediaRecorder = new MediaRecorder(dir_stream, { mimeType: 'audio/webm' });

  dir_mediaRecorder.ondataavailable = (e) => {
    if (e.data && e.data.size > 0) dir_audioChunks.push(e.data);
  };

  dir_mediaRecorder.onstop = async () => {
    try { dir_stream.getTracks().forEach(t => t.stop()); } catch (_) {}

    // crear blob pendiente para “mini resumen”
    dir_pendingBlob = new Blob(dir_audioChunks, { type: 'audio/webm' });
    dir_pendingUrl = URL.createObjectURL(dir_pendingBlob);

    // mini resumen: escuchar y decidir
    const result = await Swal.fire({
      icon: 'info',
      title: 'Audio de dirección listo',
      html: `
        <div style="text-align:left;font-size:13px;margin-bottom:8px;">
          Escúchalo antes de guardarlo:
        </div>
        <audio controls style="width:100%;">
          <source src="${dir_pendingUrl}" type="audio/webm">
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
      // Guardar => subir
      try {
        Swal.fire({
          title: 'Subiendo audio...',
          text: 'Espere un momento',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

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
      //  Regrabar → limpiar dirección y volver a grabar
       $('#inpDireccion').val('');  
      $('#direccionAudioPath').val('');
      resetPendingAudio();
      // arrancar inmediatamente otra vez
      toggleVoiceDireccion(true).catch(err => {
        console.error(err);
        Swal.fire('Error', 'No se pudo iniciar la regrabación.', 'error');
      });
    } else {
      // Descartar
      $('#direccionAudioPath').val('');
      resetPendingAudio();
      // no hacemos nada
    }
  };

  dir_mediaRecorder.start();
}

async function toggleVoiceDireccion(forceStart = false) {
  const btn = document.getElementById('btnVoiceDireccion');

  // START
  if (!dir_isRunning || forceStart) {
    // limpiar audio anterior
    $('#direccionAudioPath').val('');
    resetPendingAudio();

    dir_isRunning = true;
    btn.textContent = '🛑';
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-danger');

    // iniciar speech + recorder
    startTranscriptionDireccion();
    await startRecordingDireccion();
    return;
  }

  // STOP
  dir_isRunning = false;
  btn.textContent = '🎤⏺️';
  btn.classList.add('btn-outline-secondary');
  btn.classList.remove('btn-danger');

  // detener speech
  try { dir_recognition?.stop(); } catch (_) {}

  // detener recorder (esto dispara el mini resumen)
  try { dir_mediaRecorder?.stop(); } catch (_) {}

  // por si acaso
  try { dir_stream?.getTracks()?.forEach(t => t.stop()); } catch (_) {}
}

document.getElementById('btnVoiceDireccion')?.addEventListener('click', () => {
  toggleVoiceDireccion().catch(err => {
    console.error(err);
    Swal.fire('Error', err?.message || 'No se pudo iniciar/detener.', 'error');
  });
});

/* ======= Validaciones básicas ======= */
function validarFormulario() {
    const usuario   = $('#inpUsuario').val().trim();
    const direccion = $('#inpDireccion').val().trim();

    if (!usuario || !direccion) {
        Swal.fire('Faltan datos', 'Usuario y Dirección son obligatorios.', 'warning');
        return false;
    }

    if (!OPERADORA || OPERADORA === 'No autenticado') {
        Swal.fire('Error', 'No se pudo identificar la operadora. Por favor inicie sesión nuevamente.', 'error');
        return false;
    }

    return true;
}

/* ======= MOVILES (modal) ======= */
function cargarMoviles(q = '') {
    return $.get('{{ route("servicios.moviles") }}', { q });
}

function pintarTabla(moviles) {
    const $tbody = $('#tablaMoviles tbody').empty();
    if (!moviles.length) {
        $tbody.append('<tr><td colspan="5" class="text-muted">No hay móviles activos.</td></tr>');
        return;
    }
    moviles.forEach(m => {
        $tbody.append(`
            <tr>
                <td>${m.mo_taxi}</td>
                <td>${m.nombre_conductor ?? ''}</td>
                <td>${m.placa ?? ''}</td>
                <td>${m.cantidad}</td>
                <td><button class="btn btn-sm btn-success" onclick="asignar(${m.mo_id})">Disponible</button></td>
            </tr>
        `);
    });
}

/* ======= Asignar Servicio ======= */
async function asignar(mo_id) {
    const usuario   = $('#inpUsuario').val().trim();
    const direccion = $('#inpDireccion').val().trim();
    const audioPath = $('#direccionAudioPath').val().trim(); // puede ser vacío

    try {
        const resp = await $.ajax({
            url: '{{ route("servicios.registrar") }}',
            method: 'POST',
            data: {
                _token: csrfToken,
                conmo: mo_id,
                usuario,
                direccion,
                operadora: OPERADORA,
                audio_path: audioPath
            },
            dataType: 'json'
        });

        const token = resp.token || '---';
        const movil = resp.movil || '---';
        const placa = resp.placa || '---';

        $('#modalMoviles').modal('hide');

        setTimeout(() => {
            $('.modal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('padding-right', '');

            Swal.fire({
                icon: 'success',
                title: 'Servicio asignado',
                html: `<div style="font-size:14px">
                        Móvil: <strong>${movil}</strong><br>
                        Placa: <strong>${placa}</strong><br><br>
                        <div style="font-size:14px">Código para consulta (24h):</div>
                        <div style="font-size:32px;font-weight:800;letter-spacing:3px">${token}</div>
                       </div>`,
            }).then(() => {
                $('#inpUsuario, #inpDireccion, #direccionAudioPath').val('');
                $('#inpUsuario').focus();
            });
        }, 300);

    } catch (error) {
        console.error('❌ Error completo:', error);

        let errorMsg = 'No se pudo registrar el servicio.';
        let errorTitle = 'Error';

        if (error.status === 419) {
            errorTitle = 'Sesión expirada';
            errorMsg = 'Su sesión ha expirado. La página se recargará automáticamente.';
            Swal.fire({
                icon: 'warning',
                title: errorTitle,
                text: errorMsg,
                confirmButtonText: 'Recargar ahora',
                allowOutsideClick: false
            }).then(() => location.reload());
            return;
        }

        if (error.responseJSON && error.responseJSON.errors) {
            const errors = error.responseJSON.errors;
            errorMsg = Object.values(errors).flat().join('<br>');
        } else if (error.responseJSON && error.responseJSON.message) {
            errorMsg = error.responseJSON.message;
        }

        Swal.fire({ icon: 'error', title: errorTitle, html: errorMsg });
    }
}

/* ======= Modal y buscador interno ======= */
function debounce(fn, wait = 200) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
}

$('#btnAbrirModal').on('click', async () => {
    if (dir_isRunning) {
        Swal.fire('Grabación en curso', 'Detén la grabación/transcripción de la dirección antes de asignar.', 'warning');
        return;
    }

    if (!validarFormulario()) return;

    try {
        const moviles = await cargarMoviles('');
        pintarTabla(moviles);
        const modal = new bootstrap.Modal(document.getElementById('modalMoviles'));
        modal.show();
        setTimeout(() => document.getElementById('inpBuscarMovil')?.focus(), 300);
        $('#inpBuscarMovil').val('');
    } catch (e) {
        console.error('Error cargando móviles:', e);
        Swal.fire('Error', 'No fue posible cargar los móviles.', 'error');
    }
});

/* ======= Navegación con Enter ======= */
$('#inpUsuario').on('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); $('#inpDireccion').focus(); }
});

$('#inpDireccion').on('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); $('#btnAbrirModal').click(); }
});

/* ======= Filtrar móviles en el modal ======= */
const filtrarMoviles = debounce(async function () {
    const q = $('#inpBuscarMovil').val();
    try {
        $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-muted">Buscando...</td></tr>');
        const moviles = await cargarMoviles(q);
        pintarTabla(moviles);
    } catch (e) {
        console.error('Error filtrando móviles:', e);
        $('#tablaMoviles tbody').html('<tr><td colspan="5" class="text-danger">Error al buscar</td></tr>');
    }
}, 250);

$('#inpBuscarMovil').on('input', filtrarMoviles);
$('#inpBuscarMovil').on('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });

/* ======= Limpiar formulario ======= */
$('#btnLimpiar').on('click', () => {
    // si estaba corriendo, detenemos
    if (dir_isRunning) {
      try { dir_recognition?.stop(); } catch (_) {}
      try { dir_mediaRecorder?.stop(); } catch (_) {}
      dir_isRunning = false;
      $('#btnVoiceDireccion').text('🎤⏺️').removeClass('btn-danger').addClass('btn-outline-secondary');
    }

    $('#inpUsuario, #inpDireccion, #direccionAudioPath').val('');
    resetPendingAudio();
    $('#inpUsuario').focus();
});
</script>
@endsection
