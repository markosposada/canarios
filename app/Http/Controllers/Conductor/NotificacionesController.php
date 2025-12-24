<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $notificaciones = $user->notifications()->latest()->paginate(20);

        return view('conductor.notificaciones', compact('notificaciones'));
    }

    public function leer($id)
    {
        $user = auth()->user();

        $n = $user->notifications()->where('id', $id)->firstOrFail();

        $n->markAsRead();

        // Si quieres redirigir al servicio:
        if (!empty($n->data['servicio_id'])) {
            // Ajusta a tu ruta real de detalle si existe
            return redirect()->route('conductor.servicios_asignados');
        }

        return redirect()->route('conductor.notificaciones');
    }

    public function leerTodas()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return redirect()->route('conductor.notificaciones');
    }
}
