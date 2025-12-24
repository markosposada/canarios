@extends('layouts.app_conductor')

@section('title', 'Notificaciones')

@section('content')
<div class="row">
    <div class="col-12">
        <h3 class="mb-3">Notificaciones</h3>

        <a class="dropdown-item text-center"
   href="{{ route('conductor.notificaciones.leer_todas') }}">
    Marcar todas como leídas
</a>

        <div class="card">
            <div class="card-body">
                @forelse($notificaciones as $n)
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <div>
                            <strong>{{ $n->data['titulo'] ?? 'Notificación' }}</strong>
                            <div class="text-muted">{{ $n->data['mensaje'] ?? '' }}</div>
                            <small class="text-muted">{{ $n->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <div>
                            @if(is_null($n->read_at))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('conductor.notificaciones.leer', $n->id) }}">
                                    Marcar leída
                                </a>
                            @endif
                        </div>
                    </div>
                    <hr>
                @empty
                    <p class="text-muted mb-0">Sin notificaciones.</p>
                @endforelse

                {{ $notificaciones->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
