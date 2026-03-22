@extends('layouts.app')

@section('title', 'Dashboard - Los Canarios')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <h3 class="mb-1">
            Bienvenido, {{ $nombreUsuario }}
            @if($esAdmin)
                <span class="badge badge-dark ml-2">Administrador</span>
            @endif
        </h3>
        <p class="text-muted mb-0">
            Resumen del día ({{ $hoy }})@if($esAdmin) - vista global @endif
        </p>
    </div>
</div>

<style>
  .dash-card{
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.07);
    transition: transform .12s ease, box-shadow .12s ease;
  }
  .dash-card:hover{
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,.10);
  }
  .dash-icon{
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(0,0,0,.04);
  }
  .dash-kpi{
    font-size: 28px;
    font-weight: 900;
    margin: 0;
    line-height: 1.1;
  }
  .dash-sub{
    margin: 0;
    color: #6c757d;
    font-size: 13px;
  }
</style>

@php
    function money($n) {
        return number_format((int)$n, 0, ',', '.');
    }
@endphp

<div class="row">

    <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card dash-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Servicios asignados hoy</p>
                    <p class="dash-kpi">{{ $serviciosAsignadosHoy }}</p>
                    <p class="dash-sub">Desde disponibles</p>
                </div>
                <div class="dash-icon">
                    <i class="mdi mdi-hail mdi-28px text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card dash-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Facturas generadas hoy</p>
                    <p class="dash-kpi">{{ $facturasHoyCantidad }}</p>
                    <p class="dash-sub">Total: ${{ money($facturasHoyTotal) }}</p>
                </div>
                <div class="dash-icon">
                    <i class="mdi mdi-receipt mdi-28px text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card dash-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Pagos registrados hoy</p>
                    <p class="dash-kpi">{{ $pagosHoyCantidad }}</p>
                    <p class="dash-sub">Total: ${{ money($pagosHoyTotal) }}</p>
                </div>
                <div class="dash-icon">
                    <i class="mdi mdi-cash-register mdi-28px text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 grid-margin stretch-card">
        <div class="card dash-card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <p class="mb-1 text-muted">Pendiente de recaudo (hoy)</p>
                    <p class="dash-kpi">${{ money($pendienteHoy) }}</p>
                    <p class="dash-sub">Facturas de hoy sin pagar</p>
                </div>
                <div class="dash-icon">
                    <i class="mdi mdi-alert-circle-outline mdi-28px text-warning"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card dash-card">
            <div class="card-body d-flex flex-wrap align-items-center" style="gap:10px;">
                <a href="{{ url('/servicios/asignar') }}" class="btn btn-primary">
                    <i class="mdi mdi-hail mr-1"></i> Asignar servicio
                </a>
                <a href="{{ route('operadora.facturacion') }}" class="btn btn-success">
                    <i class="mdi mdi-cash-multiple mr-1"></i> Facturación
                </a>
                <a href="{{ route('operadora.recaudado') }}" class="btn btn-info text-white">
                    <i class="mdi mdi-cash-register mr-1"></i> Recaudado
                </a>
                <a href="{{ url('/servicios/listado') }}" class="btn btn-success">
                    <i class="mdi mdi-clipboard-list-outline mr-1"></i> Listado
                </a>
                <a href="{{ route('operadora.resumen_operadoras') }}" class="btn btn-primary">
    <i class="mdi mdi-chart-bar mr-1"></i> Resumen Operadora
</a>
            </div>
        </div>
    </div>
</div>
@endsection