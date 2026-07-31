const CACHE_NAME = 'tortas-admin-v1';
const NOTIFICATION_SOUND_INTERVAL = 5000;
let notifIntervalId = null;

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(clients.claim());
});

self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        var title = event.data.title || 'Las Tortas Del Chiche';
        var body = event.data.body || 'Nuevo pedido pendiente';
        var tag = event.data.tag || 'new-order';
        var orderId = event.data.orderId || null;

        event.waitUntil(
            self.registration.showNotification(title, {
                body: body,
                icon: '/img/icon-192.png',
                badge: '/img/icon-192.png',
                tag: tag,
                renotify: true,
                vibrate: [200, 100, 200, 100, 200],
                requireInteraction: true,
                data: { orderId: orderId, url: '/admin/orders?status=pendiente' },
                actions: [
                    { action: 'view', title: 'Ver pedido' },
                    { action: 'dismiss', title: 'Cerrar' }
                ]
            })
        );
    }

    if (event.data && event.data.type === 'STOP_SOUND') {
        if (notifIntervalId) {
            clearInterval(notifIntervalId);
            notifIntervalId = null;
        }
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'dismiss') return;

    var url = '/admin/orders?status=pendiente';
    if (event.notification.data && event.notification.data.url) {
        url = event.notification.data.url;
    }
    if (event.notification.data && event.notification.data.orderId) {
        url = '/admin/orders';
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf('/admin') !== -1 && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
