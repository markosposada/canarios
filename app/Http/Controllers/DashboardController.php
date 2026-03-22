<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $tz = 'America/Bogota';
        $hoy = now($tz)->toDateString();

        $user = Auth::user();
        $nombre = $user->name ?? $user->email ?? 'OPERADORA';
        $rol = strtolower(trim($user->rol ?? ''));
        $esAdmin = ($rol === 'administrador');

        // Servicios asignados hoy
        $qServicios = DB::table('disponibles')
            ->whereDate('dis_fecha', $hoy);

        if (!$esAdmin) {
            $qServicios->where('dis_operadora', $nombre);
        }

        $serviciosAsignadosHoy = $qServicios->count();

        // Facturas generadas hoy
        $qFacturasHoy = DB::table('facturacion_operadora')
            ->whereDate('fo_fecha', $hoy);

        if (!$esAdmin) {
            $qFacturasHoy->where('fo_operadora', $nombre);
        }

        $facturasHoy = $qFacturasHoy
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(fo_total),0) as total')
            ->first();

        $facturasHoyCantidad = (int) ($facturasHoy->cantidad ?? 0);
        $facturasHoyTotal = (int) ($facturasHoy->total ?? 0);

        // Pagos registrados hoy
        $qPagosHoy = DB::table('facturacion_operadora')
            ->where('fo_pagado', 1)
            ->whereDate('fo_pagado_at', $hoy);

        if (!$esAdmin) {
            $qPagosHoy->where('fo_pagado_operadora', $nombre);
        }

        $pagosHoy = $qPagosHoy
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(fo_total),0) as total')
            ->first();

        $pagosHoyCantidad = (int) ($pagosHoy->cantidad ?? 0);
        $pagosHoyTotal = (int) ($pagosHoy->total ?? 0);

        // Pendiente de recaudo hoy
        $qPendienteHoy = DB::table('facturacion_operadora')
            ->whereDate('fo_fecha', $hoy)
            ->where('fo_pagado', 0);

        if (!$esAdmin) {
            $qPendienteHoy->where('fo_operadora', $nombre);
        }

        $pendienteHoy = (int) $qPendienteHoy->sum('fo_total');

        return view('dashboard', [
            'nombreUsuario' => $nombre,
            'rolUsuario' => $rol,
            'esAdmin' => $esAdmin,
            'hoy' => $hoy,
            'serviciosAsignadosHoy' => $serviciosAsignadosHoy,
            'facturasHoyCantidad' => $facturasHoyCantidad,
            'facturasHoyTotal' => $facturasHoyTotal,
            'pagosHoyCantidad' => $pagosHoyCantidad,
            'pagosHoyTotal' => $pagosHoyTotal,
            'pendienteHoy' => $pendienteHoy,
        ]);
    }
}