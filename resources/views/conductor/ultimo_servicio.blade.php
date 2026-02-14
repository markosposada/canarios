{{-- resources/views/conductor/ultimo_servicio.blade.php --}}
@extends('layouts.app_conductor')

@section('title', 'Último servicio')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Carbon\Carbon;
@endphp

<div class="row">
    <div class="col-12">
        <h3 class="mb-2">ÚLTIMO SERVICIO ASIGNADO</h3>
        <p class="text-muted mb-3">
            Detalle del último servicio recibido.
        </p>
    </div>
</div>

@if(!$servicio)
    <div class="alert alert-warning">
        No tienes servicios asignados.
    </div>
@else

@php
    // ====== minutos desde la asignación ======
    $minutos = null;

    if (!empty($servicio->dis_fecha) && !empty($servicio->dis_hora)) {
        $format = strlen($servicio->dis_hora) === 5 ? 'Y-m-d H:i' : 'Y-m-d H:i:s';

        $fechaHora = Carbon::createFromFormat(
            $format,
            $servicio->dis_fecha . ' ' . $servicio->dis_hora,
            'America/Bogota'
        );

        $minutos = abs((int) now('America/Bogota')->diffInMinutes($fechaHora));

    }

    // URL del audio del servicio (si existe)
    $audioServicioUrl = !empty($servicio->dis_audio)
        ? Storage::url($servicio->dis_audio)
        : null;
@endphp

<div class="row">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body">

                {{-- Tiempo --}}
                @if($minutos !== null)
                    <div class="alert alert-info py-2">
                        <strong>Asignado hace:</strong> {{ $minutos }} minutos
                    </div>
                @endif

                <p><strong>ID Servicio:</strong> {{ $servicio->dis_id }}</p>
                <p><strong>Móvil:</strong> {{ $servicio->mo_taxi }}</p>
                <p><strong>Dirección:</strong> {{ $servicio->dis_dire }}</p>
                <p><strong>Fecha:</strong> {{ $servicio->dis_fecha }}</p>
                <p><strong>Hora:</strong> {{ $servicio->dis_hora }}</p>
                <p><strong>Usuario:</strong> {{ $servicio->dis_usuario ?? '—' }}</p>

                <hr>

                <h5 class="mb-2">Audio del servicio</h5>

                @if($audioServicioUrl)
                    <audio controls preload="metadata" style="width:100%;">
                        <source src="{{ $audioServicioUrl }}" type="audio/mpeg">
                        Tu navegador no soporta reproducción de audio.
                    </audio>
                @else
                    <p class="text-muted mb-0">
                        Este servicio no tiene audio.
                    </p>
                @endif

                <hr>

                <a href="{{ route('conductor.servicios_asignados') }}" class="btn btn-secondary">
                    Volver a servicios
                </a>

            </div>
        </div>
    </div>
</div>
@endif
@endsection
