/* ============================================================
   CART.JS — Carrito de Compra + Geolocalización + WhatsApp
   Las Tortas Del Chiche
   ============================================================ */

const Cart = (() => {
  const STORAGE_KEY = "tortas_chiche_carrito";
  const HISTORY_KEY = "tortas_chiche_historial";
  const MAX_HISTORY = 5;
  const SCHEDULE_START = 7;
  const SCHEDULE_END = 14;
  const DELIVERY_FEE = { base: 40, baseKm: 4, perKm: 10, min: 40, max: 120 };
  let VALID_COUPONS = {};
  const BANK_INFO = {
    bank: "BBVA",
    holder: "FERNANDO GUTIERREZ",
    clabe: "012180015427586084",
  };

  let BRANCHES = {};
  let BRANCHES_LOADED = false;

  function loadBranches() {
    fetch("/api/branches")
      .then((r) => r.json())
      .then((data) => {
        const map = {};
        data.forEach(function(item) {
          map[item.key] = {
            name: item.name,
            address: item.address || "",
            whatsapp: item.whatsapp || "",
            schedule: item.schedule_text || "",
            lat: item.latitude,
            lng: item.longitude,
            is_open: item.is_open,
          };
        });
        BRANCHES = map;
        BRANCHES_LOADED = true;
        const sidebar = document.getElementById("cart-sidebar");
        if (sidebar && sidebar.classList.contains("open")) {
          renderSidebar();
        }
      })
      .catch(function() {
        BRANCHES_LOADED = true;
      });
  }

  let state = {
    items: [],
    branch: "",
    payment: "efectivo",
    deliveryType: "domicilio",
    pickupTime: "",
    coupon: "",
    customer: { name: "", phone: "", addressRef: "" },
    location: { lat: null, lng: null, confirmed: false, address: null },
    paymentProof: null,
  };

  let map = null;
  let marker = null;
  let geocodeTimer = null;
  let collapsedSections = { datos: false, sucursal: false, pago: false, ubicacion: false, horario: false };
  let lastAddedItemKey = null;
  let branchAutoSelected = false;

  function generateKey(id, options) {
    const opts = options || {};
    const parts = [id];
    Object.keys(opts).sort().forEach(function(k) {
      parts.push(k + '-' + opts[k]);
    });
    return parts.join('_');
  }
  let userCoords = null;

  /* ──────────── Distancia Haversine ──────────── */
  function haversineDistance(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  function getClosestBranch(lat, lng) {
    let closest = null;
    let minDist = Infinity;
    for (const [key, branch] of Object.entries(BRANCHES)) {
      if (branch.is_open === false) continue;
      const dist = haversineDistance(lat, lng, branch.lat, branch.lng);
      if (dist < minDist) {
        minDist = dist;
        closest = key;
      }
    }
    if (!closest) {
      for (const key of Object.keys(BRANCHES)) {
        closest = key;
        break;
      }
    }
    return { key: closest, distance: minDist };
  }

  function formatDistance(km) {
    if (km < 1) return Math.round(km * 1000) + " m";
    return km.toFixed(1) + " km";
  }

  function getDeliveryFee() {
    if (state.deliveryType === "recoger") return 0;
    if (!state.location.lat || !state.location.lng) return DELIVERY_FEE.base;
    const branch = BRANCHES[state.branch];
    if (!branch) return DELIVERY_FEE.base;
    const dist = haversineDistance(branch.lat, branch.lng, state.location.lat, state.location.lng);
    const extra = Math.max(0, Math.ceil(dist) - DELIVERY_FEE.baseKm);
    return Math.min(DELIVERY_FEE.max, Math.max(DELIVERY_FEE.min, DELIVERY_FEE.base + extra * DELIVERY_FEE.perKm));
  }

  function getDeliveryDistance() {
    if (state.deliveryType !== "domicilio" || !state.location.lat || !state.location.lng) return null;
    const branch = BRANCHES[state.branch];
    if (!branch) return null;
    return haversineDistance(branch.lat, branch.lng, state.location.lat, state.location.lng);
  }

  function getCouponData() {
    if (!state.coupon) return null;
    return VALID_COUPONS[state.coupon.toUpperCase().trim()] || null;
  }

  function getCouponDiscount() {
    const coupon = getCouponData();
    if (!coupon) return 0;
    return Math.round(getTotal() * coupon.discount);
  }

  function isCouponValid() {
    return getCouponData() !== null;
  }

  function loadCoupons() {
    fetch("/api/coupons")
      .then((r) => r.json())
      .then((data) => {
        VALID_COUPONS = data;
      })
      .catch(() => {});
  }

  function getPickupHours() {
    const hours = [];
    for (let h = SCHEDULE_START; h < SCHEDULE_END; h++) {
      const suffix = h >= 12 ? "PM" : "AM";
      const h12 = h > 12 ? h - 12 : h;
      hours.push(`${h12}:00 ${suffix}`);
      hours.push(`${h12}:30 ${suffix}`);
    }
    const lastH = SCHEDULE_END > 12 ? SCHEDULE_END - 12 : SCHEDULE_END;
    hours.push(`${lastH}:00 PM`);
    return hours;
  }

  /* ──────────── Persistencia ──────────── */
  function save() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  }

  function load() {
    const data = localStorage.getItem(STORAGE_KEY);
    if (data) {
      try {
        const parsed = JSON.parse(data);
        state.items = parsed.items || [];
        state.branch = parsed.branch && BRANCHES[parsed.branch] ? parsed.branch : "";
        state.payment = parsed.payment || "efectivo";
        state.deliveryType = parsed.deliveryType || "domicilio";
        state.pickupTime = parsed.pickupTime || "";
        state.coupon = parsed.coupon || "";
        state.customer = parsed.customer || { name: "", phone: "", addressRef: "" };
        state.customer.phone = (state.customer.phone || "").replace(/\D/g, "").slice(0, 10);
        state.location = parsed.location || { lat: null, lng: null, confirmed: false, address: null };
      } catch {
    state = { items: [], branch: "", payment: "efectivo", deliveryType: "domicilio", pickupTime: "", coupon: "", customer: { name: "", phone: "", addressRef: "" }, location: { lat: null, lng: null, confirmed: false, address: null } };
      }
    }
  }

  /* ──────────── Historial de Pedidos ──────────── */
  function saveToHistory() {
    if (state.items.length === 0) return;
    const history = getHistory();
    const order = {
      date: new Date().toLocaleString("es-MX"),
      items: JSON.parse(JSON.stringify(state.items)),
      total: getTotal(),
      branch: BRANCHES[state.branch] ? BRANCHES[state.branch].name : state.branch,
      payment: state.payment === "efectivo" ? "Efectivo" : "Transferencia",
    };
    history.unshift(order);
    if (history.length > MAX_HISTORY) history.length = MAX_HISTORY;
    localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
  }

  function getHistory() {
    try {
      return JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
    } catch {
      return [];
    }
  }

  /* ──────────── Horario de Atención ──────────── */
  function isScheduleOpen() {
    const now = new Date();
    const hour = now.getHours();
    return hour >= SCHEDULE_START && hour < SCHEDULE_END;
  }

  function getScheduleMessage() {
    const now = new Date();
    const hour = now.getHours();
    if (hour < SCHEDULE_START) return `Abre a las ${SCHEDULE_START}:00 am`;
    if (hour >= SCHEDULE_END) return `Cerrado. Abre mañana a las ${SCHEDULE_START}:00 am`;
    return "";
  }

  /* ──────────── CRUD ──────────── */
  function addItem(id, name, price, img, options) {
    const key = generateKey(id, options);
    const existing = state.items.find((i) => i.key === key);
    if (existing) {
      existing.quantity++;
      lastAddedItemKey = null;
    } else {
      state.items.push({ key, id, name, price: Number(price), img: img || "", quantity: 1, options: options || {} });
      lastAddedItemKey = key;
    }
    save();
    renderBadge();
    pulseButton();
    showAddToast(name);
  }

  function removeItem(key) {
    const el = document.querySelector(`.cart-item[data-key="${key}"]`);
    if (el) {
      el.classList.add("cart-item-exit");
      setTimeout(() => {
        state.items = state.items.filter((i) => i.key !== key);
        save();
        renderSidebar();
        renderBadge();
      }, 300);
    } else {
      state.items = state.items.filter((i) => i.key !== key);
      save();
      renderSidebar();
      renderBadge();
    }
  }

  function updateQuantity(key, delta) {
    const item = state.items.find((i) => i.key === key);
    if (!item) return;
    item.quantity += delta;
    if (item.quantity <= 0) {
      removeItem(key);
      return;
    }
    save();
    updateQuantityUI(key);
    renderBadge();
    updateCardQtyDisplay(key);
  }

  function updateQuantityUI(key) {
    const item = state.items.find((i) => i.key === key);
    if (!item) return;
    const qtyEl = document.querySelector(`.cart-qty-value[data-key="${key}"]`);
    const subEl = document.querySelector(`.cart-item-subtotal[data-key="${key}"]`);
    if (qtyEl) qtyEl.textContent = item.quantity;
    if (subEl) subEl.textContent = `$${item.price * item.quantity}`;
    const totalEl = document.getElementById("cart-total-amount");
    const discount = isCouponValid() ? getCouponDiscount() : 0;
    const grandTotal = state.deliveryType === "domicilio" ? getTotal() + getDeliveryFee() - discount : getTotal() - discount;
    if (totalEl) totalEl.textContent = `$${grandTotal}`;
    const headerCount = document.getElementById("cart-header-count");
    if (headerCount) headerCount.textContent = `(${getItemCount()})`;
  }

  function getTotal() {
    return state.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
  }

  function getItemCount() {
    return state.items.reduce((sum, i) => sum + i.quantity, 0);
  }

  /* ──────────── Reverse Geocoding (Nominatim) ──────────── */
  async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=es`;
    try {
      const res = await fetch(url, {
        headers: { "User-Agent": "TortasDelChiche-Web/1.0" },
      });
      const data = await res.json();
      return data.address || null;
    } catch {
      return null;
    }
  }

  function formatAddress(addr) {
    if (!addr) return "";
    const parts = [addr.road, addr.neighbourhood || addr.suburb, addr.city || addr.town, addr.state].filter(Boolean);
    return parts.join(", ");
  }

  /* ──────────── UI: Botón flotante + Badge ──────────── */
  function getQuantityByKey(key) {
    const item = state.items.find((i) => i.key === key);
    return item ? item.quantity : 0;
  }

  function getQuantityById(id) {
    return state.items.reduce((s, i) => i.id === id ? s + i.quantity : s, 0);
  }

  function showCardQtyControl(key, id) {
    const card = document.querySelector(`.menu-item[data-id="${id}"]`);
    if (!card) return;
    const originalBtn = card.querySelector(".add-to-cart-btn");
    if (!originalBtn) return;

    if (cardTimers[id]) clearTimeout(cardTimers[id]);

    const qty = getQuantityByKey(key);
    originalBtn.outerHTML = `
      <div class="card-qty-control" data-key="${key}">
        <button class="card-qty-btn card-qty-minus" data-key="${key}" aria-label="Disminuir"><i class="fas fa-minus"></i></button>
        <span class="card-qty-value" data-key="${key}">${qty}</span>
        <button class="card-qty-btn card-qty-plus" data-key="${key}" aria-label="Aumentar"><i class="fas fa-plus"></i></button>
      </div>`;

    const control = card.querySelector(".card-qty-control");
    control.querySelector(".card-qty-minus").addEventListener("click", (e) => {
      e.stopPropagation();
      decreaseCardQty(key);
    });
    control.querySelector(".card-qty-plus").addEventListener("click", (e) => {
      e.stopPropagation();
      const c = card.closest(".menu-item");
      const opts = getItemOptionsFromCard(c);
      addItem(id, c.dataset.name, c.dataset.price, c.dataset.img, opts);
      const newKey = generateKey(id, opts);
      updateCardQtyDisplay(newKey);
    });

    cardTimers[id] = setTimeout(() => hideCardQtyControl(id), 5000);
  }

  function hideCardQtyControl(id) {
    if (cardTimers[id]) {
        clearTimeout(cardTimers[id]);
        delete cardTimers[id];
    }
    const card = document.querySelector(`.menu-item[data-id="${id}"]`);
    if (!card) return;
    const control = card.querySelector('.card-qty-control');
    if (!control) return;

    const name = card.dataset.name;
    const needsOptions = card.dataset.hasTypeOptions === '1' || card.dataset.hasMeatOptions === '1';
    const opts = getItemOptionsFromCard(card);
    const optionsOk = (!needsOptions) || (
      (card.dataset.hasTypeOptions !== '1' || opts.type) &&
      (card.dataset.hasMeatOptions !== '1' || opts.meat)
    );
    const disabledAttr = (needsOptions && !optionsOk) ? ' disabled' : '';
    const disabledClass = (needsOptions && !optionsOk) ? ' disabled' : '';

    control.outerHTML = `
      <button class="add-to-cart-btn${disabledClass}" aria-label="Agregar ${name} al carrito"${disabledAttr}>
        <i class="fas fa-cart-plus"></i> Agregar
      </button>`;

    const btn = card.querySelector(".add-to-cart-btn");
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (btn.disabled) return;
      if (card.dataset.inactive === "1") return;
      const opts = getItemOptionsFromCard(card);
      const id = Number(card.dataset.id);
      addItem(id, name, card.dataset.price, card.dataset.img, opts);
      const newKey = generateKey(id, opts);
      showCardQtyControl(newKey, id);
    });
  }

  function updateCardQtyDisplay(key) {
    const qty = getQuantityByKey(key);
    const el = document.querySelector(`.card-qty-value[data-key="${key}"]`);
    if (el) el.textContent = qty;
  }

  function decreaseCardQty(key) {
    const item = state.items.find((i) => i.key === key);
    if (!item) return;
    if (item.quantity <= 1) {
      removeItem(key);
    } else {
      item.quantity--;
      save();
      renderBadge();
      pulseButton();
    }
    updateCardQtyDisplay(key);
  }

  function syncAllCardButtons() {
    document.querySelectorAll(".menu-item").forEach((card) => {
      const id = Number(card.dataset.id);
      const opts = getItemOptionsFromCard(card);
      const key = generateKey(id, opts);
      const qty = getQuantityByKey(key);
      if (qty > 0 && !card.querySelector(".card-qty-control")) {
        const btn = card.querySelector(".add-to-cart-btn");
        if (btn) {
          btn.outerHTML = `
            <div class="card-qty-control" data-key="${key}">
              <button class="card-qty-btn card-qty-minus" data-key="${key}" aria-label="Disminuir"><i class="fas fa-minus"></i></button>
              <span class="card-qty-value" data-key="${key}">${qty}</span>
              <button class="card-qty-btn card-qty-plus" data-key="${key}" aria-label="Aumentar"><i class="fas fa-plus"></i></button>
            </div>`;
          const control = card.querySelector(".card-qty-control");
          control.querySelector(".card-qty-minus").addEventListener("click", (e) => {
            e.stopPropagation();
            decreaseCardQty(key);
          });
          control.querySelector(".card-qty-plus").addEventListener("click", (e) => {
            e.stopPropagation();
            const opts = getItemOptionsFromCard(card);
            addItem(id, card.dataset.name, card.dataset.price, card.dataset.img, opts);
            const newKey = generateKey(id, opts);
            updateCardQtyDisplay(newKey);
          });
          if (cardTimers[id]) clearTimeout(cardTimers[id]);
          cardTimers[id] = setTimeout(() => hideCardQtyControl(id), 5000);
        }
      }
    });
  }
  function createFloatingButton() {
    if (document.getElementById("cart-float-btn")) return;

    const btn = document.createElement("div");
    btn.id = "cart-float-btn";
    btn.className = "cart-float-btn";
    btn.setAttribute("role", "button");
    btn.setAttribute("aria-label", "Abrir carrito de compras");
    btn.innerHTML = `
      <i class="fas fa-shopping-cart"></i>
      <span class="cart-badge" id="cart-badge">0</span>
    `;
    btn.addEventListener("click", toggleSidebar);
    document.body.appendChild(btn);
    renderBadge();
  }

  function renderBadge() {
    const badge = document.getElementById("cart-badge");
    if (!badge) return;
    const count = getItemCount();
    badge.textContent = count;
    badge.style.display = count > 0 ? "flex" : "none";
  }

  function pulseButton() {
    const btn = document.getElementById("cart-float-btn");
    if (!btn) return;
    btn.classList.add("cart-pulse");
    setTimeout(() => btn.classList.remove("cart-pulse"), 400);
  }

  /* ──────────── UI: Sidebar ──────────── */
  function createSidebar() {
    if (document.getElementById("cart-sidebar")) return;

    const overlay = document.createElement("div");
    overlay.id = "cart-overlay";
    overlay.className = "cart-overlay";
    overlay.addEventListener("click", closeSidebar);

    const sidebar = document.createElement("div");
    sidebar.id = "cart-sidebar";
    sidebar.className = "cart-sidebar";

    sidebar.innerHTML = `
      <div class="cart-sidebar-header">
        <h2><i class="fas fa-shopping-cart"></i> Tu Pedido <span class="cart-header-count" id="cart-header-count"></span></h2>
        <button class="cart-close-btn" aria-label="Cerrar carrito">&times;</button>
      </div>
      <div class="cart-sidebar-body" id="cart-body">
        <!-- Se llena dinámicamente -->
      </div>
    `;

    sidebar.querySelector(".cart-close-btn").addEventListener("click", closeSidebar);
    document.body.appendChild(overlay);
    document.body.appendChild(sidebar);
  }

  function toggleSidebar() {
    const sidebar = document.getElementById("cart-sidebar");
    if (!sidebar) return;
    if (sidebar.classList.contains("open")) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }

  function openSidebar() {
    createSidebar();
    if (!branchAutoSelected) {
      branchAutoSelected = true;
      let userLat = null;
      let userLng = null;

      if (state.location.confirmed && state.location.lat && state.location.lng) {
        userLat = state.location.lat;
        userLng = state.location.lng;
        const { key } = getClosestBranch(userLat, userLng);
        if (key) state.branch = key;
        save();
        userCoords = { lat: userLat, lng: userLng };
        renderSidebar();
      } else {
        renderSidebar();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            (pos) => {
              userLat = pos.coords.latitude;
              userLng = pos.coords.longitude;
              const { key } = getClosestBranch(userLat, userLng);
              if (key) state.branch = key;
              save();
              userCoords = { lat: userLat, lng: userLng };
              renderSidebar();
            },
            () => {},
            { enableHighAccuracy: true, timeout: 5000 }
          );
        }
      }
    } else {
      renderSidebar();
    }

    const overlay = document.getElementById("cart-overlay");
    const sidebar = document.getElementById("cart-sidebar");
    overlay.classList.add("open");
    sidebar.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closeSidebar() {
    const overlay = document.getElementById("cart-overlay");
    const sidebar = document.getElementById("cart-sidebar");
    if (overlay) overlay.classList.remove("open");
    if (sidebar) sidebar.classList.remove("open");
    document.body.style.overflow = "";
  }

  function renderSidebar() {
    createSidebar();
    const body = document.getElementById("cart-body");
    if (!body) return;

    if (state.items.length === 0) {
      let emptyHtml = `
        <div class="cart-empty">
          <i class="fas fa-shopping-cart"></i>
          <p>Tu carrito está vacío</p>
          <span>Agrega productos del menú para comenzar tu pedido</span>
        </div>
      `;
      const history = getHistory();
      if (history.length > 0) {
        emptyHtml += `
          <div class="cart-history">
            <h3><i class="fas fa-history"></i> Pedidos recientes</h3>
            ${history.map((order) => `
              <div class="cart-history-item">
                <div class="cart-history-header">
                  <span class="cart-history-date">${order.date}</span>
                  <span class="cart-history-total">$${order.total}</span>
                </div>
                <div class="cart-history-detail">
                  ${order.items.map((i) => `${i.quantity}x ${i.name}`).join(", ")}
                </div>
                <div class="cart-history-meta">${order.branch} &middot; ${order.payment}</div>
                <button class="cart-history-reorder" data-index="${history.indexOf(order)}">
                  <i class="fas fa-redo"></i> Volver a pedir
                </button>
              </div>
            `).join("")}
          </div>
        `;
      }
      body.innerHTML = emptyHtml;
      document.querySelectorAll(".cart-history-reorder").forEach((btn) => {
        btn.addEventListener("click", () => {
          const order = history[Number(btn.dataset.index)];
          if (!order) return;
          let added = 0;
          order.items.forEach((i) => {
            const itemKey = i.key || generateKey(i.id, i.options);
            const existing = state.items.find((si) => si.key === itemKey);
            if (existing) {
              existing.quantity += i.quantity;
            } else {
              state.items.push({ ...i, key: itemKey });
              added++;
            }
          });
          save();
          renderSidebar();
          renderBadge();
          syncAllCardButtons();
          showAddToast(order.items.length + " artículos del historial");
        });
      });
      return;
    }

    const isPickup = state.deliveryType === "recoger";
    const pickupHours = getPickupHours();

    const stepsDone = {
      items: state.items.length > 0,
      datos: state.customer.name.trim() && state.customer.phone.trim(),
      sucursal: !!state.branch && !!BRANCHES[state.branch] && BRANCHES[state.branch].is_open !== false,
      horario: !isPickup || state.pickupTime,
      pago: true,
      ubicacion: isPickup || state.location.confirmed,
    };

    const steps = [
      { key: "items", label: "Items", done: stepsDone.items },
      { key: "datos", label: "Datos", done: stepsDone.datos },
    ];
    if (!isPickup) {
      steps.push({ key: "ubicacion", label: "Mapa", done: stepsDone.ubicacion });
    }
    steps.push({ key: "sucursal", label: "Sucursal", done: stepsDone.sucursal });
    steps.push({ key: "pago", label: "Pago", done: stepsDone.pago });
    if (isPickup) {
      steps.push({ key: "horario", label: "Hora", done: stepsDone.horario });
    }

    const doneCount = steps.filter((s) => s.done).length;
    let html = "";
    html += `
      <div class="cart-progress">
        <div class="cart-progress-bar"><div class="cart-progress-fill" style="width:${(doneCount / steps.length) * 100}%"></div></div>
        <div class="cart-progress-steps">
          ${steps.map((s) => `
            <span class="cart-progress-step ${s.done ? "done" : ""}" data-goto="${s.key}">
              <i class="fas fa-${s.done ? "check-circle" : "circle"}"></i> ${s.label}
            </span>
          `).join("")}
        </div>
      </div>
    `;

    html += `<div class="cart-items">`;
    state.items.forEach((item) => {
      const itemOpts = item.options || {};
      const optParts = [];
      if (itemOpts.type) optParts.push(itemOpts.type === 'mojado' ? 'Mojado' : 'Seco');
      if (itemOpts.meat) optParts.push(itemOpts.meat === 'cochinita' ? 'Cochinita' : 'Lechón');
      const optStr = optParts.length ? optParts.join(' · ') : '';
      html += `
        <div class="cart-item" data-key="${item.key}">
          ${item.img ? `<img src="${item.img}" alt="${item.name}" class="cart-item-img" />` : ""}
          <div class="cart-item-info">
            <h4>${item.name}${optStr ? ` <span class="cart-item-options">(${optStr})</span>` : ''}</h4>
            <span class="cart-item-price">$${item.price} c/u</span>
          </div>
          <div class="cart-item-controls">
            <button class="cart-qty-btn" data-key="${item.key}" data-action="decrease" aria-label="Disminuir cantidad">
              <i class="fas fa-minus"></i>
            </button>
            <span class="cart-qty-value" data-key="${item.key}">${item.quantity}</span>
            <button class="cart-qty-btn" data-key="${item.key}" data-action="increase" aria-label="Aumentar cantidad">
              <i class="fas fa-plus"></i>
            </button>
          </div>
          <div class="cart-item-subtotal" data-key="${item.key}">$${item.price * item.quantity}</div>
          <button class="cart-item-remove" data-key="${item.key}" aria-label="Eliminar ${item.name}">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
      `;
    });
    html += `</div>`;

    const couponValid = isCouponValid();
    const couponHasCode = !!state.coupon;
    html += `
      <div class="cart-coupon">
        <div class="cart-coupon-row">
          <input type="text" id="cart-coupon-input" class="cart-coupon-input" placeholder="Tienes un cupón?" value="${state.coupon}" maxlength="30" />
          <button class="cart-coupon-btn" id="cart-coupon-apply">
            <i class="fas fa-tag"></i> Aplicar
          </button>
        </div>
        ${couponValid ? `
          <div class="cart-coupon-success">
            <i class="fas fa-check-circle"></i> Cupón aplicado: ${getCouponData().label} de descuento
            <button class="cart-coupon-remove" id="cart-coupon-remove"><i class="fas fa-times"></i></button>
          </div>
        ` : ""}
        ${couponHasCode && !couponValid ? `
          <div class="cart-coupon-error">
            <i class="fas fa-times-circle"></i> Cupón no válido
          </div>
        ` : ""}
      </div>
    `;

    const deliverySummary = isPickup ? (state.pickupTime || "Elegir hora") : "Envío a domicilio";
    html += `
      <div class="cart-section">
        <div class="cart-delivery-toggle">
          <button class="cart-delivery-option ${!isPickup ? "active" : ""}" data-delivery="domicilio">
            <i class="fas fa-motorcycle"></i>
            <span class="cart-delivery-name">Domicilio</span>
          </button>
          <button class="cart-delivery-option ${isPickup ? "active" : ""}" data-delivery="recoger">
            <i class="fas fa-store"></i>
            <span class="cart-delivery-name">Recoger en sucursal</span>
          </button>
        </div>
      </div>
    `;

    const datosSummary = state.customer.name ? `${state.customer.name}${state.customer.phone ? " · " + state.customer.phone : ""}` : "Completar";
    const isDatosFilled = state.customer.name && state.customer.phone;
    html += `
      <div class="cart-section">
        <button class="cart-section-header" data-section="datos">
          <h3><i class="fas fa-user"></i> Tus datos</h3>
          <span class="cart-section-summary">${datosSummary}</span>
          <i class="fas fa-chevron-${collapsedSections.datos ? "right" : "down"}"></i>
        </button>
        <div class="cart-section-body ${collapsedSections.datos ? "collapsed" : ""}">
          <div class="cart-customer-form-inner">
            <div class="cart-form-group">
              <input type="text" id="cart-name" placeholder="Tu nombre *" value="${state.customer.name}" required />
            </div>
            <div class="cart-form-group">
              <input type="tel" id="cart-phone" placeholder="Teléfono * (10 dígitos)" value="${state.customer.phone.replace(/(\d{3})(?=\d)/g, "$1 ").trim()}" maxlength="12" required />
            </div>
            ${!isPickup ? `
              <div class="cart-form-group">
                <textarea id="cart-address" placeholder="Referencia de dirección * (casa color, frente a, etc.)" required>${state.customer.addressRef}</textarea>
              </div>
            ` : ""}
          </div>
        </div>
      </div>
    `;

    const branchKeys = Object.keys(BRANCHES);
    const branchSummary = state.branch && BRANCHES[state.branch] ? BRANCHES[state.branch].name.replace("Sucursal ", "") : (branchKeys.length > 0 ? "Elegir" : "Cargando...");
    const branchDists = {};

    branchKeys.forEach(function(key) {
      const b = BRANCHES[key];
      if (state.location.confirmed && state.location.lat && state.location.lng) {
        branchDists[key] = formatDistance(haversineDistance(state.location.lat, state.location.lng, b.lat, b.lng));
      } else if (userCoords) {
        branchDists[key] = formatDistance(haversineDistance(userCoords.lat, userCoords.lng, b.lat, b.lng));
      }
    });

    if (!isPickup) {
      const locConfirmed = state.location.confirmed;
      const addrFormatted = formatAddress(state.location.address);
      const locSummary = locConfirmed ? (state.location.address ? (state.location.address.road || "Ubicación OK") : "Ubicación OK") : "Elegir en mapa";
      html += `
        <div class="cart-section">
          <button class="cart-section-header" data-section="ubicacion">
            <h3><i class="fas fa-map-marker-alt"></i> Ubicación</h3>
            <span class="cart-section-summary">${locSummary}</span>
            <i class="fas fa-chevron-${collapsedSections.ubicacion ? "right" : "down"}"></i>
          </button>
          <div class="cart-section-body ${collapsedSections.ubicacion ? "collapsed" : ""}">
            <div class="cart-location-inner">
              <button class="cart-map-btn" id="cart-open-map">
                <i class="fas fa-map-marker-alt"></i>
                ${locConfirmed ? "Ubicación seleccionada" : "Elegir ubicación en mapa"}
              </button>
              ${locConfirmed ? `
                <div class="cart-address-preview">
                  <i class="fas fa-road"></i>
                  <div>
                    <p class="cart-address-street">${state.location.address ? (state.location.address.road || "Sin calle") : "Sin dirección"}</p>
                    <p class="cart-address-detail">${addrFormatted || "Confirma tu ubicación en el mapa"}</p>
                  </div>
                </div>
              ` : ""}
            </div>
          </div>
        </div>
      `;
    }

    var brancHtml = "";
    branchKeys.forEach(function(key) {
      const b = BRANCHES[key];
      const isActive = state.branch === key;
      const isClosed = b.is_open === false;
      brancHtml += `
        <button class="cart-branch-option ${isActive ? "active" : ""} ${isClosed ? "branch-closed" : ""}" data-branch="${key}">
          <i class="fas fa-map-marker-alt"></i>
          <span class="cart-branch-name">${b.name.replace("Sucursal ", "")}</span>
          ${isClosed ? '<span class="cart-branch-closed-badge">Cerrada ahora</span>' : ""}
          ${branchDists[key] ? `<span class="cart-branch-distance">${branchDists[key]}</span>` : ""}
          <span class="cart-branch-schedule">${b.schedule}</span>
        </button>`;
    });

    html += `
      <div class="cart-section">
        <button class="cart-section-header" data-section="sucursal">
          <h3><i class="fas fa-store"></i> Elige sucursal</h3>
          <span class="cart-section-summary">${branchSummary}</span>
          <i class="fas fa-chevron-${collapsedSections.sucursal ? "right" : "down"}"></i>
        </button>
        <div class="cart-section-body ${collapsedSections.sucursal ? "collapsed" : ""}">
          ${state.branch && BRANCHES[state.branch] && BRANCHES[state.branch].is_open === false ? `
            <div class="cart-branch-closed-msg"><i class="fas fa-exclamation-triangle"></i> Esta sucursal está cerrada. Elige otra.</div>
          ` : ""}
          <div class="cart-branch-options">
            ${brancHtml || '<p style="padding:10px 20px;color:#999;font-size:0.8rem;">No hay sucursales disponibles</p>'}
          </div>
          ${isPickup && state.branch && BRANCHES[state.branch] ? `<div class="cart-branch-address"><i class="fas fa-map-pin"></i> ${BRANCHES[state.branch].address}</div>` : ""}
        </div>
      </div>
    `;

    const paymentSummary = state.payment === "efectivo" ? "Efectivo" : "Transferencia";
    const clabeFormatted = BANK_INFO.clabe.replace(/(\d{4})(?=\d)/g, "$1 ");
    html += `
      <div class="cart-section">
        <button class="cart-section-header" data-section="pago">
          <h3><i class="fas fa-credit-card"></i> Método de pago</h3>
          <span class="cart-section-summary">${paymentSummary}</span>
          <i class="fas fa-chevron-${collapsedSections.pago ? "right" : "down"}"></i>
        </button>
        <div class="cart-section-body ${collapsedSections.pago ? "collapsed" : ""}">
          <div class="cart-payment-options">
            <button class="cart-payment-option ${state.payment === "efectivo" ? "active" : ""}" data-payment="efectivo">
              <i class="fas fa-money-bill-wave"></i>
              <span class="cart-payment-name">Efectivo</span>
            </button>
            <button class="cart-payment-option ${state.payment === "transferencia" ? "active" : ""}" data-payment="transferencia">
              <i class="fas fa-university"></i>
              <span class="cart-payment-name">Transferencia</span>
            </button>
          </div>
          <div class="bank-info-card ${state.payment === "transferencia" ? "visible" : ""}">
            <div class="bank-info-title"><i class="fas fa-university"></i> Datos para transferencia</div>
            <div class="bank-info-row">
              <i class="fas fa-building"></i>
              <span class="bank-info-label">Banco</span>
              <span class="bank-info-value">${BANK_INFO.bank}</span>
            </div>
            <div class="bank-info-row">
              <i class="fas fa-user"></i>
              <span class="bank-info-label">Titular</span>
              <span class="bank-info-value">${BANK_INFO.holder}</span>
            </div>
            <div class="bank-info-row">
              <i class="fas fa-hashtag"></i>
              <span class="bank-info-label">CLABE</span>
              <span class="bank-info-value">${clabeFormatted}</span>
              <button class="bank-info-copy" id="bank-copy-clabe" title="Copiar CLABE"><i class="fas fa-clipboard"></i></button>
            </div>
            <div class="bank-info-proof">
              <label class="bank-info-proof-label" for="cart-payment-proof">
                <i class="fas fa-camera"></i> Comprobante de transferencia
              </label>
              <input type="file" id="cart-payment-proof" accept="image/*" style="display:none;">
              <div class="bank-info-proof-preview" id="bank-proof-preview">
                <span><i class="fas fa-cloud-upload-alt"></i> Adjunta foto del comprobante</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    if (isPickup) {
      const horarioSummary = state.pickupTime || "Elegir hora";
      html += `
        <div class="cart-section">
          <button class="cart-section-header" data-section="horario">
            <h3><i class="fas fa-clock"></i> Hora de recolección</h3>
            <span class="cart-section-summary">${horarioSummary}</span>
            <i class="fas fa-chevron-${collapsedSections.horario ? "right" : "down"}"></i>
          </button>
          <div class="cart-section-body ${collapsedSections.horario ? "collapsed" : ""}">
            <div class="cart-pickup-hours">
              ${pickupHours.map((h) => `
                <button class="cart-pickup-hour ${state.pickupTime === h ? "active" : ""}" data-time="${h}">
                  <i class="fas fa-clock"></i> ${h}
                </button>
              `).join("")}
            </div>
          </div>
        </div>
      `;
    }

    const subtotal = getTotal();
    const fee = getDeliveryFee();
    const dist = getDeliveryDistance();
    const discount = isCouponValid() ? getCouponDiscount() : 0;
    const couponData = getCouponData();
    const showFee = isPickup;
    const grandTotal = showFee ? subtotal - discount : subtotal + fee - discount;
    const itemCount = getItemCount();
    html += `
      <div class="cart-footer">
        <div class="cart-items-summary">${itemCount} ${itemCount === 1 ? "artículo" : "artículos"}</div>
        ${!showFee ? `
          <div class="cart-subtotal-line">
            <span>Subtotal</span>
            <span>$${subtotal}</span>
          </div>
        ` : ""}
        ${discount > 0 ? `
          <div class="cart-discount-line">
            <span>Descuento (${couponData.label})</span>
            <span class="cart-discount-amount">-$${discount}</span>
          </div>
        ` : ""}
        ${!showFee ? `
          <div class="cart-delivery-fee-line">
            <span>Motomandado${dist ? ` (${formatDistance(dist)})` : ""}</span>
            <span class="cart-fee-amount">$${fee}</span>
          </div>
        ` : ""}
        ${discount > 0 || !showFee ? `<div class="cart-total-divider"></div>` : ""}
        <div class="cart-total">
          <span>Total</span>
          <span class="cart-total-amount" id="cart-total-amount">$${grandTotal}</span>
        </div>
        <button class="cart-whatsapp-btn" id="cart-send-whatsapp">
          <i class="fab fa-whatsapp"></i> Enviar pedido por WhatsApp
        </button>
      </div>
    `;

    body.innerHTML = html;
    const headerCount = document.getElementById("cart-header-count");
    if (headerCount) headerCount.textContent = `(${getItemCount()})`;
    if (lastAddedItemKey) {
      const newItemEl = document.querySelector(`.cart-item[data-key="${lastAddedItemKey}"]`);
      if (newItemEl) {
        newItemEl.classList.add("cart-item-enter");
      }
      lastAddedItemKey = null;
    }
    attachSidebarEvents();
  }

  function attachSidebarEvents() {
    document.querySelectorAll(".cart-qty-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const key = btn.dataset.key;
        const action = btn.dataset.action;
        updateQuantity(key, action === "increase" ? 1 : -1);
      });
    });

    const couponApply = document.getElementById("cart-coupon-apply");
    if (couponApply) {
      couponApply.addEventListener("click", () => {
        const input = document.getElementById("cart-coupon-input");
        state.coupon = (input?.value || "").trim();
        save();
        renderSidebar();
      });
    }

    const couponRemove = document.getElementById("cart-coupon-remove");
    if (couponRemove) {
      couponRemove.addEventListener("click", () => {
        state.coupon = "";
        save();
        renderSidebar();
      });
    }

    document.querySelectorAll(".cart-item-remove").forEach((btn) => {
      btn.addEventListener("click", () => {
        const key = btn.dataset.key;
        if (btn.classList.contains("confirming")) {
          removeItem(key);
        } else {
          btn.classList.add("confirming");
          btn.innerHTML = '<i class="fas fa-check"></i>';
          btn.setAttribute("aria-label", "Confirmar eliminar");
          setTimeout(() => {
            btn.classList.remove("confirming");
            btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
            btn.setAttribute("aria-label", "Eliminar producto");
          }, 2000);
        }
      });
    });

    const mapBtn = document.getElementById("cart-open-map");
    if (mapBtn) {
      mapBtn.addEventListener("click", () => {
        mapBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo mapa...';
        setTimeout(() => {
          openMapModal();
          mapBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Elegir ubicación en mapa';
        }, 400);
      });
    }

    const sendBtn = document.getElementById("cart-send-whatsapp");
    if (sendBtn) {
      sendBtn.addEventListener("click", sendToWhatsApp);
    }

    const nameInput = document.getElementById("cart-name");
    const phoneInput = document.getElementById("cart-phone");
    const addressInput = document.getElementById("cart-address");
    if (nameInput) nameInput.addEventListener("input", (e) => { state.customer.name = e.target.value; save(); });
    if (phoneInput) phoneInput.addEventListener("input", (e) => {
      const raw = e.target.value.replace(/\D/g, "").slice(0, 10);
      state.customer.phone = raw;
      const formatted = raw.replace(/(\d{3})(?=\d)/g, "$1 ");
      e.target.value = formatted;
      save();
    });
    if (addressInput) addressInput.addEventListener("input", (e) => { state.customer.addressRef = e.target.value; save(); });

    document.querySelectorAll(".cart-branch-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.branch = btn.dataset.branch;
        save();
        renderSidebar();
      });
    });

    document.querySelectorAll(".cart-payment-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.payment = btn.dataset.payment;
        save();
        document.querySelectorAll(".cart-payment-option").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const summary = btn.closest(".cart-section")?.querySelector(".cart-section-summary");
        if (summary) summary.textContent = btn.querySelector(".cart-payment-name")?.textContent || "";
        const bankCard = document.querySelector(".bank-info-card");
        if (bankCard) {
          bankCard.classList.toggle("visible", state.payment === "transferencia");
          if (state.payment === "transferencia") {
            setTimeout(() => bankCard.scrollIntoView({ behavior: "smooth", block: "nearest" }), 100);
          }
        }
      });
    });

    const copyClabeBtn = document.getElementById("bank-copy-clabe");
    if (copyClabeBtn) {
      copyClabeBtn.addEventListener("click", () => {
        navigator.clipboard.writeText(BANK_INFO.clabe).then(() => {
          copyClabeBtn.innerHTML = '<i class="fas fa-clipboard-check"></i>';
          setTimeout(() => { copyClabeBtn.innerHTML = '<i class="fas fa-clipboard"></i>'; }, 1500);
        });
      });
    }

    const proofInput = document.getElementById("cart-payment-proof");
    const proofPreview = document.getElementById("bank-proof-preview");
    if (proofInput && proofPreview) {
      if (state.paymentProof) {
        const reader = new FileReader();
        reader.onload = function(e) {
          proofPreview.innerHTML = '<img src="' + e.target.result + '" alt="Comprobante">';
        };
        reader.readAsDataURL(state.paymentProof);
      }
      proofInput.addEventListener("change", function() {
        if (this.files && this.files[0]) {
          state.paymentProof = this.files[0];
          save();
          const reader = new FileReader();
          reader.onload = function(e) {
            proofPreview.innerHTML = '<img src="' + e.target.result + '" alt="Comprobante">';
          };
          reader.readAsDataURL(state.paymentProof);
        }
      });
      proofPreview.addEventListener("click", function() {
        proofInput.click();
      });
    }

    document.querySelectorAll(".cart-delivery-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        const newType = btn.dataset.delivery;
        if (state.deliveryType === newType) return;
        state.deliveryType = newType;
        state.pickupTime = "";
        save();
        renderSidebar();
      });
    });

    document.querySelectorAll(".cart-pickup-hour").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.pickupTime = btn.dataset.time;
        save();
        document.querySelectorAll(".cart-pickup-hour").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const summary = btn.closest(".cart-section")?.querySelector(".cart-section-summary");
        if (summary) summary.textContent = state.pickupTime;
      });
    });

    document.querySelectorAll(".cart-section-header").forEach((btn) => {
      btn.addEventListener("click", () => {
        const section = btn.dataset.section;
        collapsedSections[section] = !collapsedSections[section];
        const body = btn.nextElementSibling;
        const icon = btn.querySelector(".fa-chevron-down, .fa-chevron-right");
        if (body) body.classList.toggle("collapsed");
        if (icon) {
          icon.classList.remove("fa-chevron-down", "fa-chevron-right");
          icon.classList.add(collapsedSections[section] ? "fa-chevron-right" : "fa-chevron-down");
        }
      });
    });

    document.querySelectorAll(".cart-progress-step").forEach((step) => {
      step.addEventListener("click", () => {
        const target = step.dataset.goto;
        if (target === "items") {
          document.querySelector(".cart-items")?.scrollIntoView({ behavior: "smooth", block: "start" });
        } else {
          const section = document.querySelector(`[data-section="${target}"]`);
          if (section) {
            if (collapsedSections[target]) {
              collapsedSections[target] = false;
              const body = section.nextElementSibling;
              const icon = section.querySelector(".fa-chevron-down, .fa-chevron-right");
              if (body) body.classList.remove("collapsed");
              if (icon) {
                icon.classList.remove("fa-chevron-right");
                icon.classList.add("fa-chevron-down");
              }
            }
            setTimeout(() => section.scrollIntoView({ behavior: "smooth", block: "start" }), 100);
          }
        }
      });
    });
  }

  /* ──────────── Mapa (Leaflet + OpenStreetMap) ──────────── */
  function openMapModal() {
    if (document.getElementById("cart-map-overlay")) return;

    const overlay = document.createElement("div");
    overlay.id = "cart-map-overlay";
    overlay.className = "cart-map-overlay";

    const container = document.createElement("div");
    container.className = "cart-map-container";
    container.innerHTML = `
      <div class="cart-map-header">
        <h3><i class="fas fa-map-marker-alt"></i> Elige tu ubicación de entrega</h3>
        <button class="cart-map-close" aria-label="Cerrar mapa">&times;</button>
      </div>
      <div class="cart-map-instruction">Arrastra el marcador para ajustar tu ubicación exacta</div>
      <div id="cart-leaflet-map" class="cart-leaflet-map"></div>
      <div id="cart-map-address-preview" class="cart-map-address-preview">
        <i class="fas fa-spinner fa-spin"></i> <span>Obteniendo dirección...</span>
      </div>
      <div class="cart-map-actions">
        <button class="cart-geo-btn" id="cart-my-location">
          <i class="fas fa-crosshairs"></i> Mi ubicación actual
        </button>
        <button class="cart-map-confirm" id="cart-confirm-location">
          <i class="fas fa-check"></i> Confirmar ubicación
        </button>
      </div>
    `;

    overlay.appendChild(container);
    document.body.appendChild(overlay);
    document.body.style.overflow = "hidden";

    container.querySelector(".cart-map-close").addEventListener("click", closeMapModal);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeMapModal();
    });

    setTimeout(initMap, 100);
  }

  function initMap() {
    const mapEl = document.getElementById("cart-leaflet-map");
    if (!mapEl || typeof L === "undefined") return;

    const defaultLat = 17.9866;
    const defaultLng = -92.9531;

    const startLat = state.location.lat || defaultLat;
    const startLng = state.location.lng || defaultLng;

    map = L.map("cart-leaflet-map").setView([startLat, startLng], 16);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(map);

    const icon = L.divIcon({
      className: "cart-marker-icon",
      html: '<div class="cart-marker-pin"><i class="fas fa-map-marker-alt"></i></div>',
      iconSize: [32, 42],
      iconAnchor: [16, 42],
    });

    marker = L.marker([startLat, startLng], { icon, draggable: true }).addTo(map);
    marker.bindPopup("Tu ubicación de entrega").openPopup();

    marker.on("dragend", () => {
      const pos = marker.getLatLng();
      updateMapAddressPreview(pos.lat, pos.lng);
    });

    setTimeout(() => map.invalidateSize(), 200);

    document.getElementById("cart-my-location").addEventListener("click", centerOnUser);
    document.getElementById("cart-confirm-location").addEventListener("click", confirmLocation);

    updateMapAddressPreview(startLat, startLng);
  }

  function updateMapAddressPreview(lat, lng) {
    const previewEl = document.getElementById("cart-map-address-preview");
    if (!previewEl) return;
    previewEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Obteniendo dirección...</span>';
    clearTimeout(geocodeTimer);
    geocodeTimer = setTimeout(async () => {
      const addr = await reverseGeocode(lat, lng);
      if (!previewEl) return;
      if (addr) {
        const street = addr.road || "";
        const detail = [addr.neighbourhood || addr.suburb, addr.city || addr.town, addr.state].filter(Boolean).join(", ");
        previewEl.innerHTML = `
          <i class="fas fa-map-pin"></i>
          <div>
            <span class="cart-map-address-street">${street}</span>
            <span class="cart-map-address-detail">${detail}</span>
          </div>
        `;
      } else {
        previewEl.innerHTML = '<i class="fas fa-info-circle"></i> <span>No se pudo obtener la dirección</span>';
      }
    }, 500);
  }

  function centerOnUser() {
    if (!navigator.geolocation) return;
    const btn = document.getElementById("cart-my-location");
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        map.setView([latitude, longitude], 17);
        marker.setLatLng([latitude, longitude]);
        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Mi ubicación actual';
        btn.disabled = false;
      },
      () => {
        btn.innerHTML = '<i class="fas fa-crosshairs"></i> No se pudo ubicar';
        btn.disabled = false;
        setTimeout(() => {
          btn.innerHTML = '<i class="fas fa-crosshairs"></i> Mi ubicación actual';
        }, 2000);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }

  async function confirmLocation() {
    const pos = marker.getLatLng();
    state.location.lat = pos.lat;
    state.location.lng = pos.lng;
    state.location.confirmed = true;
    state.location.address = await reverseGeocode(pos.lat, pos.lng);
    save();
    closeMapModal();
    renderSidebar();
  }

  function closeMapModal() {
    const overlay = document.getElementById("cart-map-overlay");
    if (overlay) {
      overlay.remove();
      document.body.style.overflow = "";
    }
    if (map) {
      map.remove();
      map = null;
      marker = null;
    }
  }

  /* ──────────── WhatsApp ──────────── */
  function sendToWhatsApp() {
    state.customer.name = document.getElementById("cart-name")?.value || state.customer.name;
    state.customer.phone = document.getElementById("cart-phone")?.value || state.customer.phone;
    state.customer.addressRef = document.getElementById("cart-address")?.value || state.customer.addressRef;
    save();

    const isPickup = state.deliveryType === "recoger";

    if (state.items.length === 0) {
      showCartAlert("Agrega productos a tu carrito primero.");
      return;
    }
    if (!state.customer.name.trim()) {
      showCartAlert("Ingresa tu nombre.");
      openSection("datos");
      setTimeout(() => document.getElementById("cart-name")?.scrollIntoView({ behavior: "smooth", block: "center" }), 200);
      return;
    }
    if (!state.customer.phone.trim()) {
      showCartAlert("Ingresa tu teléfono.");
      openSection("datos");
      setTimeout(() => document.getElementById("cart-phone")?.scrollIntoView({ behavior: "smooth", block: "center" }), 200);
      return;
    }
    if (!/^\d{10}$/.test(state.customer.phone.replace(/\D/g, ""))) {
      showCartAlert("El teléfono debe tener 10 dígitos.");
      openSection("datos");
      setTimeout(() => document.getElementById("cart-phone")?.scrollIntoView({ behavior: "smooth", block: "center" }), 200);
      return;
    }
    if (!isPickup && !state.customer.addressRef.trim()) {
      showCartAlert("Agrega una referencia de dirección.");
      openSection("datos");
      setTimeout(() => document.getElementById("cart-address")?.scrollIntoView({ behavior: "smooth", block: "center" }), 200);
      return;
    }
    if (!state.branch || !BRANCHES[state.branch]) {
      showCartAlert("Selecciona una sucursal.");
      openSection("sucursal");
      return;
    }
    if (BRANCHES[state.branch].is_open === false) {
      showCartAlert("La sucursal seleccionada está cerrada. Elige otra.");
      openSection("sucursal");
      return;
    }
    if (isPickup) {
      if (!state.pickupTime) {
        showCartAlert("Selecciona la hora de recolección.");
        openSection("horario");
        return;
      }
    } else {
      if (!state.location.confirmed) {
        showCartAlert("Selecciona tu ubicación de entrega en el mapa.");
        openSection("ubicacion");
        return;
      }
    }

    const subtotal = getTotal();
    const fee = getDeliveryFee();
    const dist = getDeliveryDistance();
    const discount = isCouponValid() ? getCouponDiscount() : 0;
    const couponData = getCouponData();
    const grandTotal = isPickup ? subtotal - discount : subtotal + fee - discount;
    const mapsLink = `https://www.google.com/maps/search/?api=1&query=${state.location.lat},${state.location.lng}`;
    const addrText = formatAddress(state.location.address);

    let msg = `*Pedido - Las Tortas Del Chiche*\n`;
    msg += `\n`;
    msg += `*Tipo:* ${isPickup ? "Recoger en sucursal" : "Envío a domicilio"}`;
    msg += `\n*Sucursal:* ${BRANCHES[state.branch] ? BRANCHES[state.branch].name : state.branch}`;
    msg += `\n*Cliente:* ${state.customer.name}`;
    msg += `\n*Tel:* ${state.customer.phone}`;
    if (isPickup) {
      msg += `\n*Hora de recolección:* ${state.pickupTime}`;
      if (state.customer.addressRef.trim()) {
        msg += `\n*Nota:* ${state.customer.addressRef}`;
      }
    } else {
      msg += `\n*Referencia:* ${state.customer.addressRef}`;
      if (addrText) {
        msg += `\n*Dirección:* ${addrText}`;
      }
      msg += `\n*Ubicación:* ${mapsLink}`;
    }
    msg += `\n`;
    msg += `\n-----------------------`;
    msg += `\n*Detalle del pedido:*\n`;
    state.items.forEach((item) => {
      const itemOpts = item.options || {};
      const optParts = [];
      if (itemOpts.type) optParts.push(itemOpts.type === 'mojado' ? 'Mojado' : 'Seco');
      if (itemOpts.meat) optParts.push(itemOpts.meat === 'cochinita' ? 'Cochinita' : 'Lechón');
      const optStr = optParts.length ? ` [${optParts.join(' - ')}]` : '';
      msg += `*${item.quantity}x* ${item.name}${optStr} — $${item.price * item.quantity}\n`;
    });
    msg += `-----------------------`;
    if (!isPickup) {
      msg += `\n*Subtotal:* $${subtotal} MXN`;
    }
    if (discount > 0) {
      msg += `\n*Descuento (${couponData.label}):* -$${discount} MXN`;
    }
    if (!isPickup) {
      msg += `\n*Motomandado${dist ? ` (${formatDistance(dist)})` : ""}:* $${fee} MXN`;
    }
    msg += `\n*Total: $${grandTotal} MXN*`;
    msg += `\n*Método de pago:* ${state.payment === "efectivo" ? "Efectivo" : "Transferencia"}`;
    if (state.payment === "transferencia") {
      msg += `\n*Banco:* ${BANK_INFO.bank}`;
      msg += `\n*Titular:* ${BANK_INFO.holder}`;
      msg += `\n*CLABE:* ${BANK_INFO.clabe.replace(/(\d{4})(?=\d)/g, "$1 ")}`;
      msg += `\n⚠️ *Adjunta tu comprobante de transferencia*`;
    }

    const url = `https://wa.me/${BRANCHES[state.branch] ? BRANCHES[state.branch].whatsapp : ""}?text=${encodeURIComponent(msg)}`;
    const sendBtn = document.getElementById("cart-send-whatsapp");
    if (sendBtn) {
      sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando pedido...';
      sendBtn.disabled = true;
    }

    const orderPayload = {
      customer_name: state.customer.name.trim(),
      customer_phone: state.customer.phone.replace(/\D/g, ""),
      customer_address: isPickup ? null : formatAddress(state.location.address),
      branch: state.branch,
      delivery_type: isPickup ? "recoger" : "domicilio",
      payment_method: state.payment,
      subtotal: subtotal,
      delivery_fee: fee,
      discount: discount,
      total: grandTotal,
      coupon_code: isCouponValid() ? state.coupon : null,
      items: state.items.map((item) => ({
        product_id: item.id || null,
        product_name: item.name,
        quantity: item.quantity,
        unit_price: item.price,
        options: item.options && Object.keys(item.options).length ? item.options : null,
      })),
    };

    let fetchPromise;
    if (state.paymentProof && state.payment === "transferencia") {
      const fd = new FormData();
      Object.keys(orderPayload).forEach(function(key) {
        if (Array.isArray(orderPayload[key])) {
          orderPayload[key].forEach(function(item, i) {
            Object.keys(item).forEach(function(k) {
              if (k === "options" && typeof item[k] === "object" && item[k] !== null) {
                Object.keys(item[k]).forEach(function(optKey) {
                  fd.append("items[" + i + "][options][" + optKey + "]", item[k][optKey]);
                });
              } else {
                fd.append("items[" + i + "][" + k + "]", item[k] !== null ? item[k] : "");
              }
            });
          });
        } else if (orderPayload[key] !== null) {
          fd.append(key, orderPayload[key]);
        }
      });
      fd.append("payment_proof", state.paymentProof);
      fetchPromise = fetch("/api/orders", { method: "POST", headers: { "Accept": "application/json" }, body: fd });
    } else {
      fetchPromise = fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(orderPayload),
      });
    }

    fetchPromise
    .then(function(r) {
      if (!r.ok) throw new Error("HTTP " + r.status);
      if (sendBtn) sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo WhatsApp...';
      const opened = window.open(url, "_blank", "noopener,noreferrer");
      if (!opened) {
        navigator.clipboard.writeText(msg).then(function() {
          showCartAlert("No se pudo abrir WhatsApp. El mensaje se copió al portapapeles. Pégalo en WhatsApp manualmente.");
        }).catch(function() {
          showCartAlert("No se pudo abrir WhatsApp. Activa las ventanas emergentes e intenta de nuevo.");
        });
      }
    })
    .catch(function() {
      if (sendBtn) sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo WhatsApp...';
      const opened = window.open(url, "_blank", "noopener,noreferrer");
      if (!opened) {
        navigator.clipboard.writeText(msg).then(function() {
          showCartAlert("No se pudo guardar el pedido, pero el mensaje se copió al portapapeles.");
        }).catch(function() {
          showCartAlert("Error al enviar. Intenta de nuevo.");
        });
      }
    })
    .finally(() => {
      saveToHistory();
      state = { items: [], branch: "atasta", payment: "efectivo", deliveryType: "domicilio", pickupTime: "", coupon: "", customer: { name: "", phone: "", addressRef: "" }, location: { lat: null, lng: null, confirmed: false, address: null }, paymentProof: null };
      save();
      renderSidebar();
      renderBadge();
      closeSidebar();
      if (sendBtn) {
        sendBtn.innerHTML = '<i class="fab fa-whatsapp"></i> Enviar pedido por WhatsApp';
        sendBtn.disabled = false;
      }
    });
  }

  function openSection(name) {
    if (collapsedSections[name]) {
      collapsedSections[name] = false;
      const btn = document.querySelector(`[data-section="${name}"]`);
      if (btn) {
        const body = btn.nextElementSibling;
        const icon = btn.querySelector(".fa-chevron-down, .fa-chevron-right");
        if (body) body.classList.remove("collapsed");
        if (icon) {
          icon.classList.remove("fa-chevron-right");
          icon.classList.add("fa-chevron-down");
        }
      }
    }
  }

  function showToast(msg, type) {
    let container = document.getElementById("cart-toast-container");
    if (!container) {
      container = document.createElement("div");
      container.id = "cart-toast-container";
      container.className = "cart-toast-container";
      document.body.appendChild(container);
    }
    const toast = document.createElement("div");
    toast.className = `cart-toast-item ${type || ""}`;
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("show"));
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  function showCartAlert(msg) {
    showToast(msg, "error");
  }

  function showAddToast(name) {
    let toast = document.getElementById("cart-add-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "cart-add-toast";
      toast.className = "cart-add-toast";
      document.body.appendChild(toast);
    }
    toast.innerHTML = `
      <div class="cart-add-toast-content">
        <i class="fas fa-check-circle"></i>
        <span><strong>${name}</strong> agregado al carrito</span>
      </div>
      <button class="cart-add-toast-btn" id="cart-add-toast-view">Ver carrito</button>
    `;
    toast.classList.add("show");
    document.getElementById("cart-add-toast-view").addEventListener("click", () => {
      toast.classList.remove("show");
      openSidebar();
    });
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove("show"), 3000);
  }

  /* ──────────── Init: Botones "Agregar" ──────────── */
  let cardTimers = {};

  function getItemOptionsFromCard(card) {
    const options = {};
    const typeSelected = card.querySelector('[data-option="type"] .option-btn.selected');
    const meatSelected = card.querySelector('[data-option="meat"] .option-btn.selected');
    if (typeSelected) options.type = typeSelected.dataset.value;
    if (meatSelected) options.meat = meatSelected.dataset.value;
    return options;
  }

  function bindAddButtons() {
    document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
      if (btn._cartBound) return;
      btn._cartBound = true;
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const card = btn.closest(".menu-item");
        if (!card || card.dataset.inactive === "1") return;
        if (btn.disabled) return;
        const id = Number(card.dataset.id);
        const name = card.dataset.name;
        const price = card.dataset.price;
        const img = card.dataset.img;
        const options = getItemOptionsFromCard(card);
        const key = generateKey(id, options);
        addItem(id, name, price, img, options);
        showCardQtyControl(key, id);
      });
    });
  }

  function initCartAddButtons() {
    bindAddButtons();
  }

  function init() {
    load();
    loadCoupons();
    loadBranches();

    bindAddButtons();

    createFloatingButton();
    createSidebar();
    renderBadge();

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const sidebar = document.getElementById("cart-sidebar");
        if (sidebar && sidebar.classList.contains("open")) {
          closeSidebar();
        }
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  window.initCartAddButtons = initCartAddButtons;

  return { addItem, removeItem, openSidebar, closeSidebar };
})();
