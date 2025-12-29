@extends('layouts.app_conductor')

@section('title', 'Mi Perfil')

@section('content')
@php
  $activo = ((int)$conductor->conduc_estado === 1);
@endphp

<div class="row justify-content-center">
  <div class="col-md-10 col-lg-8">

    @if(session('ok'))
      <div class="alert alert-success" style="border-radius:14px;">
        {{ session('ok') }}
      </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius:18px; overflow:hidden;">
      {{-- Header --}}
      <div class="p-4 text-white"
           style="background: linear-gradient(135deg, #111827 0%, #6d28d9 45%, #22c55e 100%);">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h4 class="mb-1">🧾 Mi Perfil</h4>
            <div class="opacity-75">Actualiza tus datos personales.</div>
          </div>

          <div class="mt-3 mt-md-0 text-right">
            <div class="badge badge-pill {{ $activo ? 'badge-success' : 'badge-danger' }}"
                 style="padding:10px 14px;font-size:12px;">
              {{ $activo ? 'ACTIVO' : 'INACTIVO' }}
            </div>
            <div class="mt-2 badge badge-pill badge-light" style="padding:10px 14px;">
              Cédula: <strong>{{ $conductor->conduc_cc }}</strong>
            </div>
          </div>
        </div>
      </div>

      {{-- Body --}}
      <div class="card-body p-4">
        <form method="POST" action="{{ route('conductor.perfil.update') }}">
          @csrf

          {{-- Tarjeta “bloqueados” --}}
          <div class="p-3 mb-4"
               style="border-radius:16px;background:#f8fafc;border:1px solid #e5e7eb;">
            <div class="d-flex align-items-center">
              <i class="mdi mdi-lock-outline" style="font-size:28px;margin-right:10px;"></i>
              <div>
                <div style="font-weight:800;">Campos protegidos</div>
                <div class="text-muted" style="font-size:13px;">
                  La cédula y el estado no se pueden modificar desde aquí.
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            {{-- Nombres --}}
            <div class="col-md-12 mb-3">
              <label class="font-weight-bold">Nombres</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-account"></i></span>
                </div>
                <input type="text"
                       name="conduc_nombres"
                       class="form-control @error('conduc_nombres') is-invalid @enderror"
                       value="{{ old('conduc_nombres', $conductor->conduc_nombres) }}"
                       placeholder="Ej: Juan Pérez">
                @error('conduc_nombres')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Licencia --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">Licencia</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-card-account-details"></i></span>
                </div>
                <input type="number"
                       name="conduc_licencia"
                       class="form-control @error('conduc_licencia') is-invalid @enderror"
                       value="{{ old('conduc_licencia', $conductor->conduc_licencia) }}"
                       placeholder="Número licencia">
                @error('conduc_licencia')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Ingrese el numero de licencia</small>
            </div>

            {{-- Fecha --}}
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold">Fecha</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-calendar"></i></span>
                </div>
                <input type="date"
                       name="conduc_fecha"
                       class="form-control @error('conduc_fecha') is-invalid @enderror"
                       value="{{ old('conduc_fecha', $conductor->conduc_fecha) }}">
                @error('conduc_fecha')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Si esta vencida, se recomienda renovarla</small>
            </div>

            {{-- Celular --}}
            <div class="col-md-6 mb-2">
              <label class="font-weight-bold">Celular</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-cellphone"></i></span>
                </div>
                <input
    type="tel"
    name="conduc_cel"
    class="form-control"
    inputmode="numeric"
    pattern="[0-9]*"
    maxlength="10"
    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
    value="{{ old('conduc_cel', $conductor->conduc_cel ?? '') }}"
>

                @error('conduc_cel')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Solo números, máximo 10 dígitos.</small>
            </div>

            {{-- Panel motivacional --}}
            <div class="col-md-6 mb-2">
              <div class="p-3"
                   style="border-radius:16px;border:1px dashed #c7d2fe;background:#eef2ff;height:100%;">
                <div style="font-weight:900;">✨ Consejo rápido</div>
                <div class="text-muted" style="font-size:13px;">
                  Mantén tu celular actualizado para que la operadora pueda contactarte si hay novedades.
                </div>
                <div class="mt-2">
                  <span class="badge badge-pill badge-primary" style="padding:8px 10px;">Los Canarios</span>
                  <span class="badge badge-pill badge-light" style="padding:8px 10px;">Conductor</span>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap">
            <a href="{{ url('/conductor/servicios-asignados') }}" class="btn btn-light" style="border-radius:14px;">
              <i class="mdi mdi-arrow-left"></i> Volver
            </a>

            <button type="submit" class="btn btn-primary" style="border-radius:14px; font-weight:900; padding:12px 18px;">
              <i class="mdi mdi-content-save"></i> Guardar cambios
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>
@endsection
