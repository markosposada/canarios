<!-- resources/views/modulos/operadora.blade.php -->
<h1>Módulo propietario</h1>
<a href="{{ route('logout') }}"
   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
   class="btn btn-danger mt-3">
    Cerrar sesión
</a>
<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>