@extends('layouts.app_conductor')

@section('title', 'Mi Taxi')

@section('content')
@php
  // Si quieres mostrar estado del taxi como badge
  $estadoTaxi = strtoupper($taxi->ta_estado ?? 'A'); // A/I o lo que uses
  $isActivo = ($estadoTaxi === 'A' || $estadoTaxi === '1' || $estadoTaxi === 'ACTIVO');
@endphp

<div class="row justify-content-center">
  <div class="col-md-10 col-lg-8">

    @if(session('ok'))
      <div class="alert alert-success" style="border-radius:14px;">
        {{ session('ok') }}
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger" style="border-radius:14px;">
        <ul class="mb-0">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius:18px; overflow:hidden;">
      {{-- Header --}}
      <div class="p-4 text-white"
           style="background: linear-gradient(135deg, #0f172a 0%, #2563eb 45%, #22c55e 100%);">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h4 class="mb-1">🚖 Mi Taxi</h4>
            <div class="opacity-75">Actualiza la información de tu vehículo asignado.</div>
          </div>

          <div class="mt-3 mt-md-0 text-right">
            <div class="badge badge-pill {{ $isActivo ? 'badge-success' : 'badge-danger' }}"
                 style="padding:10px 14px;font-size:12px;">
              {{ $isActivo ? 'ACTIVO' : 'INACTIVO' }}
            </div>

            <div class="mt-2 badge badge-pill badge-light" style="padding:10px 14px;">
              ID Taxi: <strong>{{ $taxi->ta_movil }}</strong>
            </div>
          </div>
        </div>
      </div>

      {{-- Body --}}
      <div class="card-body p-4">
        <form method="POST" action="{{ route('conductor.taxi.update') }}">
          @csrf

          {{-- Tarjeta info --}}
          <div class="p-3 mb-4"
               style="border-radius:16px;background:#f8fafc;border:1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
              <i class="mdi mdi-shield-check-outline" style="font-size:28px;margin-right:10px;"></i>
              <div>
                <div style="font-weight:800;">Edición protegida</div>
                <div class="text-muted" style="font-size:13px;">
                  Solo puedes modificar el taxi que tienes asignado.
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            {{-- Placa --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">Placa</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-card-text-outline"></i></span>
                </div>
                <input type="text"
                       name="ta_placa"
                       maxlength="10"
                       style="text-transform: uppercase;"
                       class="form-control @error('ta_placa') is-invalid @enderror"
                       value="{{ old('ta_placa', $taxi->ta_placa) }}"
                       placeholder="Ej: SSU050"
                       readonly>
                @error('ta_placa')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Verifica que sea la placa del automovil</small>
            </div>

            {{-- Estado (solo lectura, por si quieres mostrarlo) --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">Estado</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-toggle-switch"></i></span>
                </div>
                <input type="text"
                       class="form-control"
                       value="{{ $taxi->ta_estado ?? '' }}"
                       readonly>
              </div>
              <small class="text-muted">Este estado lo gestiona la administración.</small>
            </div>

            {{-- SOAT --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">SOAT</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-file-document-outline"></i></span>
                </div>
                <input type="date"
                       name="ta_soat"
                       class="form-control @error('ta_soat') is-invalid @enderror"
                       value="{{ old('ta_soat', ($taxi->ta_soat ?? '') === '0000-00-00' ? '' : ($taxi->ta_soat ?? '')) }}">
                @error('ta_soat')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Recomendado mantenerlo vigente.</small>
            </div>

            {{-- Tecnomecánica --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">Tecnomecánica</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-wrench-outline"></i></span>
                </div>
                <input type="date"
                       name="ta_tecno"
                       class="form-control @error('ta_tecno') is-invalid @enderror"
                       value="{{ old('ta_tecno', ($taxi->ta_tecno ?? '') === '0000-00-00' ? '' : ($taxi->ta_tecno ?? '')) }}">
                @error('ta_tecno')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Recomendado mantenerlo vigente.</small>
            </div>

            {{-- Panel motivacional --}}
            <div class="col-md-12 mt-2">
              <div class="p-3"
                   style="border-radius:16px;border:1px dashed #bae6fd;background:#eff6ff;">
                <div style="font-weight:900;">🛡️ Consejo rápido</div>
                <div class="text-muted" style="font-size:13px;">
                  Tener SOAT y tecnomecánica al día te evita sanciones y mejora la confianza del servicio.
                </div>
                <div class="mt-2">
                  <span class="badge badge-pill badge-primary" style="padding:8px 10px;">Los Canarios</span>
                  <span class="badge badge-pill badge-light" style="padding:8px 10px;">Mi Taxi</span>
                </div>
              </div>
            </div>

          </div>

          <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap">
            <a href="{{ url('/modulo-conductor') }}" class="btn btn-light" style="border-radius:14px;">
              <i class="mdi mdi-arrow-left"></i> Volver
            </a><p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap">
            <button type="submit" class="btn btn-primary"
                    style="border-radius:14px; font-weight:900; padding:12px 18px;">
              <i class="mdi mdi-content-save"></i> Guardar cambios
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>
@endsection
