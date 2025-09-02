@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-center mb-4">Bienvenido al Sistema de Taxis Los Canarios</h2>

    <div class="row row-cols-1 row-cols-md-2 g-4">

        {{-- Panel de taxis --}}
        <div class="col">
            <div class="card h-100 shadow border-start border-5 border-dark">
                <div class="card-body">
                    <h5 class="card-title">🚖 Panel de Taxis</h5>
                    <p class="card-text">Ver todos los taxis, su estado, activar/inactivar o asignar nuevos.</p>
                    <a href="{{ url('/taxis/panel') }}" class="btn btn-dark w-100">Ir al Panel</a>
                </div>
            </div>
        </div>

        {{-- Editar fechas --}}
        <div class="col">
            <div class="card h-100 shadow border-start border-5 border-primary">
                <div class="card-body">
                    <h5 class="card-title">📅 Editar Fechas</h5>
                    <p class="card-text">Actualizar fechas de SOAT y Tecnomecánica de un taxi ya registrado.</p>
                    <a href="{{ url('/taxis/editar-fechas') }}" class="btn btn-primary w-100">Editar Fechas</a>
                </div>
            </div>
        </div>

        {{-- Registrar usuario --}}
        <div class="col">
            <div class="card h-100 shadow border-start border-5 border-success">
                <div class="card-body">
                    <h5 class="card-title">👤 Registrar Usuario</h5>
                    <p class="card-text">Crear un nuevo usuario del sistema con su respectivo rol.</p>
                    <a href="{{ url('/register') }}" class="btn btn-success w-100">Registrar</a>
                </div>
            </div>
        </div>



 <div class="col-md-4">
            <a href="{{ route('conductores.editLicencia') }}" class="btn btn-warning w-100">Editar Licencia de Conductor</a>
        </div>

        <div class="col-md-3">
    <a href="{{ url('/conductores') }}" class="btn btn-outline-primary w-100 mb-3">
        Panel Conductores
    </a>
</div>




        {{-- Asignar taxi --}}
        <div class="col">
            <div class="card h-100 shadow border-start border-5 border-warning">
                <div class="card-body">
                    <h5 class="card-title">➕ Asignar Taxi</h5>
                    <p class="card-text">Agregar los datos del vehículo a un móvil pendiente.</p>
                    <a href="{{ url('/taxis/crear') }}" class="btn btn-warning w-100">Asignar Taxi</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h5 class="card-title">Asignar Conductor</h5>
            <p class="card-text">Asocia un conductor con un móvil disponible.</p>
            <a href="{{ route('asignar.form') }}" class="btn btn-outline-warning">Ir a asignar</a>
        </div>
    </div>
</div>


    </div>
</div>
@endsection
