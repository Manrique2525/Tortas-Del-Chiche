(function () {
    var STORAGE_KEY = 'admin_pending_orders';
    var PAID_KEY = 'admin_last_paid_id';
    var ACTIVE_NOTIF_KEY = 'admin_active_notification';
    var POLL_INTERVAL = 15000;
    var lastCount = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    var lastPaidId = parseInt(localStorage.getItem(PAID_KEY) || '0', 10);
    var audioCtx = null;
    var notifRepeatId = null;
    var swRegistration = null;

    /* ──────────── Service Worker ──────────── */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            swRegistration = reg;
        }).catch(function () {});
    }

    /* ──────────── Notification Permission ──────────── */
    function requestNotificationPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    function hasNotificationPermission() {
        return 'Notification' in window && Notification.permission === 'granted';
    }

    requestNotificationPermission();

    /* ──────────── Sounds ──────────── */
    function playNotifSound() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();

            var o1 = audioCtx.createOscillator();
            var g1 = audioCtx.createGain();
            o1.connect(g1); g1.connect(audioCtx.destination);
            o1.type = 'sine';
            o1.frequency.setValueAtTime(880, audioCtx.currentTime);
            g1.gain.setValueAtTime(0.4, audioCtx.currentTime);
            g1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
            o1.start(audioCtx.currentTime);
            o1.stop(audioCtx.currentTime + 0.5);

            setTimeout(function () {
                var o2 = audioCtx.createOscillator();
                var g2 = audioCtx.createGain();
                o2.connect(g2); g2.connect(audioCtx.destination);
                o2.type = 'sine';
                o2.frequency.setValueAtTime(1100, audioCtx.currentTime);
                g2.gain.setValueAtTime(0.4, audioCtx.currentTime);
                g2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
                o2.start(audioCtx.currentTime);
                o2.stop(audioCtx.currentTime + 0.4);
            }, 200);

            setTimeout(function () {
                var o3 = audioCtx.createOscillator();
                var g3 = audioCtx.createGain();
                o3.connect(g3); g3.connect(audioCtx.destination);
                o3.type = 'sine';
                o3.frequency.setValueAtTime(1320, audioCtx.currentTime);
                g3.gain.setValueAtTime(0.4, audioCtx.currentTime);
                g3.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
                o3.start(audioCtx.currentTime);
                o3.stop(audioCtx.currentTime + 0.6);
            }, 500);
        } catch (e) {}
    }

    function playPaidSound() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();

            [660, 880, 660].forEach(function (freq, i) {
                setTimeout(function () {
                    var o = audioCtx.createOscillator();
                    var g = audioCtx.createGain();
                    o.connect(g); g.connect(audioCtx.destination);
                    o.type = 'sine';
                    o.frequency.setValueAtTime(freq, audioCtx.currentTime);
                    g.gain.setValueAtTime(0.35, audioCtx.currentTime);
                    g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
                    o.start(audioCtx.currentTime);
                    o.stop(audioCtx.currentTime + 0.4);
                }, i * 300);
            });
        } catch (e) {}
    }

    function startRepeatingSound(type) {
        stopRepeatingSound();
        if (type === 'paid') {
            playPaidSound();
        } else {
            playNotifSound();
        }
        notifRepeatId = setInterval(function () {
            if (type === 'paid') {
                playPaidSound();
            } else {
                playNotifSound();
            }
        }, 5000);
    }

    function stopRepeatingSound() {
        if (notifRepeatId) {
            clearInterval(notifRepeatId);
            notifRepeatId = null;
        }
        localStorage.removeItem(ACTIVE_NOTIF_KEY);
        if (swRegistration) {
            swRegistration.active.postMessage({ type: 'STOP_SOUND' });
        }
    }

    /* ──────────── Native Notification ──────────── */
    function showNativeNotification(title, body, tag, orderId) {
        if (swRegistration && swRegistration.active) {
            swRegistration.active.postMessage({
                type: 'SHOW_NOTIFICATION',
                title: title,
                body: body,
                tag: tag,
                orderId: orderId
            });
            return;
        }

        if (hasNotificationPermission()) {
            try {
                var n = new Notification(title, {
                    body: body,
                    icon: '/img/icon-192.png',
                    badge: '/img/icon-192.png',
                    tag: tag,
                    renotify: true,
                    vibrate: [200, 100, 200, 100, 200],
                    requireInteraction: true
                });
                n.onclick = function () {
                    window.focus();
                    window.location.href = '/admin/orders?status=pendiente';
                    n.close();
                };
            } catch (e) {}
        }
    }

    /* ──────────── Toast ──────────── */
    function showToast(message, isPaid, orderId) {
        var existing = document.getElementById('admin-notify-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        var bgColor = isPaid ? '#2ecc71' : '#FF6B35';
        toast.id = 'admin-notify-toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;background:' + bgColor + ';color:white;padding:16px 24px;border-radius:12px;font-family:Poppins,sans-serif;font-size:0.9rem;font-weight:600;z-index:10000;box-shadow:0 5px 20px rgba(0,0,0,0.3);display:flex;align-items:center;gap:10px;animation:notifySlideIn 0.4s ease;max-width:380px;cursor:pointer;';
        toast.innerHTML = '<i class="fas ' + (isPaid ? 'fa-credit-card' : 'fa-bell') + '" style="font-size:1.3rem;"></i> ' + message +
            '<span onclick="event.stopPropagation();document.getElementById(\'admin-notify-toast\').remove();" style="margin-left:auto;font-size:1.2rem;opacity:0.7;cursor:pointer;">&times;</span>';
        toast.onclick = function () {
            stopRepeatingSound();
            window.location.href = isPaid ? '/admin/orders?status=pagado' : '/admin/orders?status=pendiente';
        };
        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(function () { toast.remove(); }, 300);
        }, 15000);
    }

    /* ──────────── Badges ──────────── */
    function updateBadges(count) {
        var badges = document.querySelectorAll('[data-pending-badge]');
        badges.forEach(function (b) {
            b.textContent = count;
            b.style.display = count > 0 ? 'flex' : 'none';
        });
        var navBadges = document.querySelectorAll('[data-pending-nav]');
        navBadges.forEach(function (b) {
            b.textContent = count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }

    /* ──────────── Dismiss helpers ──────────── */
    window.dismissOrderNotification = function () {
        stopRepeatingSound();
        var toast = document.getElementById('admin-notify-toast');
        if (toast) toast.remove();
    };

    /* ──────────── Check Pending Orders ──────────── */
    function checkPendingOrders() {
        fetch('/api/orders/pending-count')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var current = data.count || 0;
                localStorage.setItem(STORAGE_KEY, String(current));
                updateBadges(current);

                if (current > lastCount) {
                    var diff = current - lastCount;
                    var msg = diff + ' nuevo' + (diff > 1 ? 's' : '') + ' pedido' + (diff > 1 ? 's' : '') + ' pendiente' + (diff > 1 ? 's' : '') + ' 🔔';

                    startRepeatingSound('pending');
                    showToast(msg, false, null);
                    showNativeNotification(
                        'Las Tortas Del Chiche',
                        msg,
                        'new-pending-orders',
                        null
                    );
                    localStorage.setItem(ACTIVE_NOTIF_KEY, 'pending');
                }
                lastCount = current;
            })
            .catch(function () {});
    }

    /* ──────────── Check Paid Orders ──────────── */
    function checkPaidOrders() {
        fetch('/admin/orders/check-paid')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.id && data.id > lastPaidId) {
                    lastPaidId = data.id;
                    localStorage.setItem(PAID_KEY, String(lastPaidId));

                    var msg = '💳 Pago con tarjeta: ' + data.customer_name + ' — $' + Math.round(data.total);
                    startRepeatingSound('paid');
                    showToast(msg, true, data.id);
                    showNativeNotification(
                        '💳 Pago recibido',
                        data.customer_name + ' — $' + Math.round(data.total),
                        'new-paid-' + data.id,
                        data.id
                    );
                    localStorage.setItem(ACTIVE_NOTIF_KEY, 'paid');
                }
            })
            .catch(function () {});
    }

    /* ──────────── Restore active notification on page reload ──────────── */
    var activeNotif = localStorage.getItem(ACTIVE_NOTIF_KEY);
    if (activeNotif) {
        var storedCount = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        if (storedCount > 0) {
            startRepeatingSound(activeNotif === 'paid' ? 'paid' : 'pending');
            var restoreMsg = activeNotif === 'paid'
                ? '💳 Pago con tarjeta pendiente de revisar'
                : storedCount + ' pedido' + (storedCount > 1 ? 's' : '') + ' pendiente' + (storedCount > 1 ? 's' : '');
            showToast(restoreMsg, activeNotif === 'paid', null);
        } else {
            localStorage.removeItem(ACTIVE_NOTIF_KEY);
        }
    }

    /* ──────────── Styles ──────────── */
    var style = document.createElement('style');
    style.textContent = '@keyframes notifySlideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
    document.head.appendChild(style);

    /* ──────────── Start polling ──────────── */
    checkPendingOrders();
    checkPaidOrders();
    setInterval(function () {
        checkPendingOrders();
        checkPaidOrders();
    }, POLL_INTERVAL);
})();
