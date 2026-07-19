(function () {
    var STORAGE_KEY = 'admin_pending_orders';
    var PAID_KEY = 'admin_last_paid_id';
    var POLL_INTERVAL = 30000;
    var lastCount = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    var lastPaidId = parseInt(localStorage.getItem(PAID_KEY) || '0', 10);
    var audioCtx = null;

    function playNotifSound() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            var o1 = audioCtx.createOscillator();
            var g1 = audioCtx.createGain();
            o1.connect(g1); g1.connect(audioCtx.destination);
            o1.type = 'sine';
            o1.frequency.setValueAtTime(880, audioCtx.currentTime);
            g1.gain.setValueAtTime(0.3, audioCtx.currentTime);
            g1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
            o1.start(audioCtx.currentTime);
            o1.stop(audioCtx.currentTime + 0.5);
            setTimeout(function () {
                var o2 = audioCtx.createOscillator();
                var g2 = audioCtx.createGain();
                o2.connect(g2); g2.connect(audioCtx.destination);
                o2.type = 'sine';
                o2.frequency.setValueAtTime(1100, audioCtx.currentTime);
                g2.gain.setValueAtTime(0.3, audioCtx.currentTime);
                g2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
                o2.start(audioCtx.currentTime);
                o2.stop(audioCtx.currentTime + 0.4);
            }, 200);
        } catch (e) {}
    }

    function playPaidSound() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            var o = audioCtx.createOscillator();
            var g = audioCtx.createGain();
            o.connect(g); g.connect(audioCtx.destination);
            o.type = 'sine';
            o.frequency.setValueAtTime(660, audioCtx.currentTime);
            g.gain.setValueAtTime(0.3, audioCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.8);
            o.start(audioCtx.currentTime);
            o.stop(audioCtx.currentTime + 0.8);
        } catch (e) {}
    }

    function showToast(message, isPaid) {
        var existing = document.getElementById('admin-notify-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        var bgColor = isPaid ? '#2ecc71' : '#FF6B35';
        toast.id = 'admin-notify-toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;background:' + bgColor + ';color:white;padding:16px 24px;border-radius:12px;font-family:Poppins,sans-serif;font-size:0.9rem;font-weight:600;z-index:10000;box-shadow:0 5px 20px rgba(0,0,0,0.3);display:flex;align-items:center;gap:10px;animation:notifySlideIn 0.4s ease;max-width:350px;cursor:pointer;';
        toast.innerHTML = '<i class="fas ' + (isPaid ? 'fa-credit-card' : 'fa-bell') + '" style="font-size:1.2rem;"></i> ' + message;
        toast.onclick = function () {
            window.location.href = isPaid ? '/admin/orders?status=pagado' : '/admin/orders?status=pendiente';
        };
        document.body.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(function () { toast.remove(); }, 300);
        }, 8000);
    }

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

    function checkPendingOrders() {
        fetch('/api/orders/pending-count')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var current = data.count || 0;
                localStorage.setItem(STORAGE_KEY, String(current));
                updateBadges(current);
                if (current > lastCount) {
                    var diff = current - lastCount;
                    playNotifSound();
                    showToast(diff + ' nuevo' + (diff > 1 ? 's' : '') + ' pedido' + (diff > 1 ? 's' : '') + ' pendiente' + (diff > 1 ? 's' : '') + ' 🔔', false);
                }
                lastCount = current;
            })
            .catch(function () {});
    }

    function checkPaidOrders() {
        fetch('/admin/orders/check-paid')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.id && data.id > lastPaidId) {
                    lastPaidId = data.id;
                    localStorage.setItem(PAID_KEY, String(lastPaidId));
                    playPaidSound();
                    showToast('💳 Nuevo pago con tarjeta: ' + data.customer_name + ' — $' + Math.round(data.total), true);
                }
            })
            .catch(function () {});
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes notifySlideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
    document.head.appendChild(style);

    checkPendingOrders();
    checkPaidOrders();
    setInterval(function () {
        checkPendingOrders();
        checkPaidOrders();
    }, POLL_INTERVAL);
})();
