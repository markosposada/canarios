<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guardado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="modal fade" id="okModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Registro exitoso</h5>
      </div>
      <div class="modal-body">
        Sus datos han sido guardados exitosamente, gracias.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnIrInicio">Ir al inicio</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('okModal');
    const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    modal.show();

    const goHome = () => window.location.href = "{{ url('/') }}";

    document.getElementById('btnIrInicio').addEventListener('click', goHome);

    // Redirige automáticamente luego de 2.2s
    setTimeout(goHome, 2200);
  });
</script>
</body>
</html>
