{{-- resources/views/servicios/consulta-token.blade.php --}}
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Consulta de servicio por token</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Bootstrap opcional para estilos rápidos --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .token-box {
      letter-spacing: .25rem;
      font-weight: 800;
      font-size: clamp(22px, 3.2vw, 32px);
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 760px">
    <h1 class="h3 text-center mb-4">Consulta de servicio por token</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="input-group">
          <input
            type="text"
            id="inpToken"
            class="form-control"
            placeholder="Ingresa el código (ej: A07)"
            inputmode="text"
            pattern="^[A-Za-z]\d{2}$"
            maxlength="3"
            aria-label="Código: 1 letra y 2 números"
            style="text-transform: uppercase;"
          >
          <button class="btn btn-dark" id="btnConsultar" type="button">Consultar</button>
        </div>
        <small class="text-muted d-block mt-2">El código es válido por 24 horas.</small>
      </div>
    </div>

    <div id="resultado" class="mt-4"></div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
  // --- Sanitiza la entrada: 1 letra + 2 números (ej: A07) ---
  (function enforceAlphaNumToken() {
    const $t = $('#inpToken');

    $t.on('input', function () {
      let v = this.value.toUpperCase();

      // 1) Mantener solo letras/números
      v = v.replace(/[^A-Z0-9]/g, '');

      // 2) Forzar formato: primera posición letra, siguientes hasta 2 dígitos
      let out = '';

      // primera: letra
      if (v.length > 0) {
        const first = v[0];
        if (/[A-Z]/.test(first)) {
          out = first;
        }
      }

      // resto: solo dígitos (máx 2)
      if (v.length > 1) {
        const digits = v.slice(1).replace(/\D/g, '').slice(0, 2);
        out += digits;
      }

      this.value = out;
    });

    // Enter para consultar
    $t.on('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); consultar(); }
    });

    $('#btnConsultar').on('click', consultar);
  })();

  function renderEstado(message, type='info') {
    const $r = $('#resultado').empty();
    const $alert = $('<div>').addClass(`alert alert-${type}`).text(message);
    $r.append($alert);
  }

  function renderResultadoSeguro(data) {
    const $r = $('#resultado').empty();

    if (!data || !data.found) {
      const msg = (data && data.message) ? data.message : 'Token no encontrado o vencido.';
      return renderEstado(msg, 'warning');
    }

    // Construimos DOM de forma segura
    const $card = $('<div>').addClass('card shadow-sm');
    const $body = $('<div>').addClass('card-body');

    const $title = $('<h5>').addClass('card-title mb-3').text('Detalles del servicio');

    const $row = $('<div>').addClass('row g-2');

    const $conductor = $('<div>').addClass('col-md-6')
      .append($('<strong>').text('Conductor: '), $('<span>').text(data.conductor ?? '-'));

    const $movil = $('<div>').addClass('col-md-3')
      .append($('<strong>').text('Móvil: '), $('<span>').text(data.movil ?? '-'));

    const $placa = $('<div>').addClass('col-md-3')
      .append($('<strong>').text('Placa: '), $('<span>').text(data.placa ?? '-'));

    const $direccion = $('<div>').addClass('col-12')
      .append($('<strong>').text('Dirección: '), $('<span>').text(data.direccion ?? '-'));

    const $fecha = $('<div>').addClass('col-md-6')
      .append($('<strong>').text('Fecha: '), $('<span>').text(data.fecha ?? '-'));

    const $hora = $('<div>').addClass('col-md-6')
      .append($('<strong>').text('Hora: '), $('<span>').text(data.hora ?? '-'));

    const valorFmt = (data.valor == null)
      ? '-'
      : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(Number(data.valor));

    const $valor = $('<div>').addClass('col-12')
      .append($('<strong>').text('Valor: '), $('<span>').text(valorFmt));

    $row.append($conductor, $movil, $placa, $direccion, $fecha, $hora, $valor);
    $body.append($title, $row);
    $card.append($body);
    $r.append($card);
  }

  async function consultar() {
    const token = ($('#inpToken').val() || '').trim().toUpperCase();
    if (!/^[A-Z]\d{2}$/.test(token)) {
      return renderEstado('El código debe ser 1 letra seguida de 2 números (ej: A07).', 'warning');
    }
    try {
      const url = `{{ route('servicios.consulta.buscar') }}?token=${encodeURIComponent(token)}`;
      const res = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' }});

      let data = null;
      try { data = await res.json(); } catch (_e) {}

      if (!res.ok) {
        return renderEstado((data && data.message) ? data.message : 'Error consultando el token.', 'danger');
      }

      renderResultadoSeguro(data);
    } catch (e) {
      renderEstado('Error de red al consultar el token.', 'danger');
    }
  }
  </script>
</body>
</html>
