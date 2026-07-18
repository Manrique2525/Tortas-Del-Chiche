(function () {
    var STORAGE_KEY = 'admin_pending_orders';
    var POLL_INTERVAL = 30000;
    var lastCount = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
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

    function showToast(message) {
        var existing = document.getElementById('admin-notify-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.id = 'admin-notify-toast';
        toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#FF6B35;color:white;padding:16px 24px;border-radius:12px;font-family:Poppins,sans-serif;font-size:0.9rem;font-weight:600;z-index:10000;box-shadow:0 5px 20px rgba(255,107,53,0.4);display:flex;align-items:center;gap:10px;animation:notifySlideIn 0.4s ease;max-width:350px;cursor:pointer;';
        toast.innerHTML = '<i class="fas fa-bell" style="font-size:1.2rem;"></i> ' + message;
        toast.onclick = function () {
            window.location.href = '/admin/orders?status=pendiente';
        };
        document.body.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(function () { toast.remove(); }, 300);
        }, 6000);
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

    function checkOrders() {
        fetch('/api/orders/pending-count')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var current = data.count || 0;
                localStorage.setItem(STORAGE_KEY, String(current));
                updateBadges(current);
                if (current > lastCount) {
                    var diff = current - lastCount;
                    playNotifSound();
                    showToast(diff + ' nuevo' + (diff > 1 ? 's' : '') + ' pedido' + (diff > 1 ? 's' : '') + ' pendiente' + (diff > 1 ? 's' : '') + ' 🔔');
                }
                lastCount = current;
            })
            .catch(function () {});
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes notifySlideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
    document.head.appendChild(style);

    checkOrders();
    setInterval(checkOrders, POLL_INTERVAL);
})();
