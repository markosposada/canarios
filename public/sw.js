self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data.json(); } catch (e) {}

  const title = data.title || 'Los Canarios';
  const options = {
    body: data.body || 'Nuevo servicio asignado',
    icon: '/assets/images/favicon.png',
    badge: '/assets/images/favicon.png',
    data: data.data || {}
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/conductor/servicios-asignados';
  event.waitUntil(clients.openWindow(url));
});
