<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar acceso</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<form id="formAdmin" method="POST" action="{{ route('guardar.acceso') }}">
    @csrf
    <input type="hidden" name="modo_ingreso" value="administrador">
</form>

<form id="formConductor" method="POST" action="{{ route('guardar.acceso') }}">
    @csrf
    <input type="hidden" name="modo_ingreso" value="conductor">
</form>

<script>
Swal.fire({
    title: '¿Cómo deseas ingresar?',
    text: 'Selecciona si quieres entrar como administrador o conductor.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Administrador',
    cancelButtonText: 'Conductor',
    allowOutsideClick: false,
    allowEscapeKey: false
}).then((result) => {
    if (result.isConfirmed) {
        document.getElementById('formAdmin').submit();
    } else {
        document.getElementById('formConductor').submit();
    }
});
</script>

</body>
</html>