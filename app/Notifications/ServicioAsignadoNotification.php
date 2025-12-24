<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServicioAsignadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $payload
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'servicio_asignado',
            'titulo' => 'Nuevo servicio asignado',
            'mensaje' => 'Tienes un nuevo servicio: ' . ($this->payload['direccion'] ?? ''),
            'servicio_id' => $this->payload['servicio_id'] ?? null,
            'direccion' => $this->payload['direccion'] ?? null,
            'movil' => $this->payload['movil'] ?? null,
            'token' => $this->payload['token'] ?? null,
        ];
    }
}
