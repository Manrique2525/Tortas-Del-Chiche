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

  const BRANCHES = {
    atasta: {
      name: "Sucursal Atasta",
      address: "Av. 27 de Febrero #2616, Colonia Atasta",
      whatsapp: "529933092124",
      schedule: "7am - 2pm",
    },
    av_universidad: {
      name: "Sucursal AV Universidad",
      address: "Av Universidad 392, Colonia Casa Blanca",
      whatsapp: "529932206325",
      schedule: "7am - 2pm",
    },
  };

  let state = {
    items: [],
    branch: "atasta",
    payment: "efectivo",
    customer: { name: "", phone: "", addressRef: "" },
    location: { lat: null, lng: null, confirmed: false, address: null },
  };

  let map = null;
  let marker = null;
  let geocodeTimer = null;
  let collapsedSections = { datos: false, sucursal: false, pago: false, ubicacion: false };
  let lastAddedItemId = null;

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
        state.branch = parsed.branch || "atasta";
        state.payment = parsed.payment || "efectivo";
        state.customer = parsed.customer || { name: "", phone: "", addressRef: "" };
        state.customer.phone = (state.customer.phone || "").replace(/\D/g, "").slice(0, 10);
        state.location = parsed.location || { lat: null, lng: null, confirmed: false, address: null };
      } catch {
        state = { items: [], branch: "atasta", payment: "efectivo", customer: { name: "", phone: "", addressRef: "" }, location: { lat: null, lng: null, confirmed: false, address: null } };
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
      branch: BRANCHES[state.branch].name,
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
  function addItem(id, name, price, img) {
    const existing = state.items.find((i) => i.id === id);
    if (existing) {
      existing.quantity++;
      lastAddedItemId = null;
    } else {
      state.items.push({ id, name, price: Number(price), img: img || "", quantity: 1, notes: "" });
      lastAddedItemId = id;
    }
    save();
    renderBadge();
    pulseButton();
    showAddToast(name);
  }

  function removeItem(id) {
    const el = document.querySelector(`.cart-item[data-id="${id}"]`);
    if (el) {
      el.classList.add("cart-item-exit");
      const notesEl = el.nextElementSibling;
      if (notesEl && notesEl.classList.contains("cart-item-notes")) {
        notesEl.classList.add("cart-item-exit");
      }
      setTimeout(() => {
        state.items = state.items.filter((i) => i.id !== id);
        save();
        renderSidebar();
        renderBadge();
      }, 300);
    } else {
      state.items = state.items.filter((i) => i.id !== id);
      save();
      renderSidebar();
      renderBadge();
    }
  }

  function updateQuantity(id, delta) {
    const item = state.items.find((i) => i.id === id);
    if (!item) return;
    item.quantity += delta;
    if (item.quantity <= 0) {
      removeItem(id);
      return;
    }
    save();
    updateQuantityUI(id);
    renderBadge();
  }

  function updateQuantityUI(id) {
    const item = state.items.find((i) => i.id === id);
    if (!item) return;
    const qtyEl = document.querySelector(`.cart-qty-value[data-id="${id}"]`);
    const subEl = document.querySelector(`.cart-item-subtotal[data-id="${id}"]`);
    if (qtyEl) qtyEl.textContent = item.quantity;
    if (subEl) subEl.textContent = `$${item.price * item.quantity}`;
    const totalEl = document.getElementById("cart-total-amount");
    if (totalEl) totalEl.textContent = `$${getTotal()}`;
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
    renderSidebar();
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
            const existing = state.items.find((si) => si.id === i.id);
            if (existing) {
              existing.quantity += i.quantity;
            } else {
              state.items.push({ ...i, notes: i.notes || "" });
              added++;
            }
          });
          save();
          renderSidebar();
          renderBadge();
          showAddToast(order.items.length + " artículos del historial");
        });
      });
      return;
    }

    const stepsDone = {
      items: state.items.length > 0,
      datos: state.customer.name.trim() && state.customer.phone.trim(),
      sucursal: true,
      pago: true,
      ubicacion: state.location.confirmed,
    };
    const doneCount = Object.values(stepsDone).filter(Boolean).length;
    let html = "";
    html += `
      <div class="cart-progress">
        <div class="cart-progress-bar"><div class="cart-progress-fill" style="width:${(doneCount / 5) * 100}%"></div></div>
        <div class="cart-progress-steps">
          <span class="cart-progress-step ${stepsDone.items ? "done" : ""}" data-goto="items"><i class="fas fa-${stepsDone.items ? "check-circle" : "circle"}"></i> Items</span>
          <span class="cart-progress-step ${stepsDone.datos ? "done" : ""}" data-goto="datos"><i class="fas fa-${stepsDone.datos ? "check-circle" : "circle"}"></i> Datos</span>
          <span class="cart-progress-step ${stepsDone.sucursal ? "done" : ""}" data-goto="sucursal"><i class="fas fa-${stepsDone.sucursal ? "check-circle" : "circle"}"></i> Sucursal</span>
          <span class="cart-progress-step ${stepsDone.pago ? "done" : ""}" data-goto="pago"><i class="fas fa-${stepsDone.pago ? "check-circle" : "circle"}"></i> Pago</span>
          <span class="cart-progress-step ${stepsDone.ubicacion ? "done" : ""}" data-goto="ubicacion"><i class="fas fa-${stepsDone.ubicacion ? "check-circle" : "circle"}"></i> Mapa</span>
        </div>
      </div>
    `;

    html += `<div class="cart-items">`;
    state.items.forEach((item) => {
      html += `
        <div class="cart-item" data-id="${item.id}">
          ${item.img ? `<img src="${item.img}" alt="${item.name}" class="cart-item-img" />` : ""}
          <div class="cart-item-info">
            <h4>${item.name}</h4>
            <span class="cart-item-price">$${item.price} c/u</span>
          </div>
          <div class="cart-item-controls">
            <button class="cart-qty-btn" data-id="${item.id}" data-action="decrease" aria-label="Disminuir cantidad">
              <i class="fas fa-minus"></i>
            </button>
            <span class="cart-qty-value" data-id="${item.id}">${item.quantity}</span>
            <button class="cart-qty-btn" data-id="${item.id}" data-action="increase" aria-label="Aumentar cantidad">
              <i class="fas fa-plus"></i>
            </button>
          </div>
          <div class="cart-item-subtotal" data-id="${item.id}">$${item.price * item.quantity}</div>
          <button class="cart-item-remove" data-id="${item.id}" aria-label="Eliminar ${item.name}">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
        <div class="cart-item-notes">
          <input type="text" class="cart-item-note-input" data-id="${item.id}" placeholder="Ej: sin cebolla, extra salsa..." value="${item.notes || ""}" />
        </div>
      `;
    });
    html += `</div>`;

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
            <div class="cart-form-group">
              <textarea id="cart-address" placeholder="Referencia de dirección * (casa color, frente a, etc.)" required>${state.customer.addressRef}</textarea>
            </div>
          </div>
        </div>
      </div>
    `;

    const branchSummary = BRANCHES[state.branch].name.replace("Sucursal ", "");
    html += `
      <div class="cart-section">
        <button class="cart-section-header" data-section="sucursal">
          <h3><i class="fas fa-store"></i> Elige sucursal</h3>
          <span class="cart-section-summary">${branchSummary}</span>
          <i class="fas fa-chevron-${collapsedSections.sucursal ? "right" : "down"}"></i>
        </button>
        <div class="cart-section-body ${collapsedSections.sucursal ? "collapsed" : ""}">
          <div class="cart-branch-options">
            <button class="cart-branch-option ${state.branch === "atasta" ? "active" : ""}" data-branch="atasta">
              <i class="fas fa-map-marker-alt"></i>
              <span class="cart-branch-name">Atasta</span>
              <span class="cart-branch-schedule">${BRANCHES.atasta.schedule}</span>
            </button>
            <button class="cart-branch-option ${state.branch === "av_universidad" ? "active" : ""}" data-branch="av_universidad">
              <i class="fas fa-map-marker-alt"></i>
              <span class="cart-branch-name">AV Universidad</span>
              <span class="cart-branch-schedule">${BRANCHES.av_universidad.schedule}</span>
            </button>
          </div>
        </div>
      </div>
    `;

    const paymentSummary = state.payment === "efectivo" ? "Efectivo" : "Transferencia";
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
        </div>
      </div>
    `;

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

    const total = getTotal();
    const itemCount = getItemCount();
    html += `
      <div class="cart-footer">
        <div class="cart-items-summary">${itemCount} ${itemCount === 1 ? "artículo" : "artículos"}</div>
        <div class="cart-total">
          <span>Total</span>
          <span class="cart-total-amount" id="cart-total-amount">$${total}</span>
        </div>
        <button class="cart-whatsapp-btn" id="cart-send-whatsapp">
          <i class="fab fa-whatsapp"></i> Enviar pedido por WhatsApp
        </button>
      </div>
    `;

    body.innerHTML = html;
    const headerCount = document.getElementById("cart-header-count");
    if (headerCount) headerCount.textContent = `(${getItemCount()})`;
    if (lastAddedItemId) {
      const newItemEl = document.querySelector(`.cart-item[data-id="${lastAddedItemId}"]`);
      if (newItemEl) {
        newItemEl.classList.add("cart-item-enter");
        const notesEl = newItemEl.nextElementSibling;
        if (notesEl && notesEl.classList.contains("cart-item-notes")) {
          notesEl.classList.add("cart-item-enter");
        }
      }
      lastAddedItemId = null;
    }
    attachSidebarEvents();
  }

  function attachSidebarEvents() {
    document.querySelectorAll(".cart-qty-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = Number(btn.dataset.id);
        const action = btn.dataset.action;
        updateQuantity(id, action === "increase" ? 1 : -1);
      });
    });

    document.querySelectorAll(".cart-item-remove").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = Number(btn.dataset.id);
        if (btn.classList.contains("confirming")) {
          removeItem(id);
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

    document.querySelectorAll(".cart-item-note-input").forEach((input) => {
      input.addEventListener("input", (e) => {
        const id = Number(input.dataset.id);
        const item = state.items.find((i) => i.id === id);
        if (item) { item.notes = e.target.value; save(); }
      });
    });

    document.querySelectorAll(".cart-branch-option").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.branch = btn.dataset.branch;
        save();
        document.querySelectorAll(".cart-branch-option").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        const summary = btn.closest(".cart-section")?.querySelector(".cart-section-summary");
        if (summary) summary.textContent = btn.querySelector(".cart-branch-name")?.textContent || "";
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
    if (!state.customer.addressRef.trim()) {
      showCartAlert("Agrega una referencia de dirección.");
      openSection("datos");
      setTimeout(() => document.getElementById("cart-address")?.scrollIntoView({ behavior: "smooth", block: "center" }), 200);
      return;
    }
    if (!state.location.confirmed) {
      showCartAlert("Selecciona tu ubicación de entrega en el mapa.");
      openSection("ubicacion");
      return;
    }

    const total = getTotal();
    const mapsLink = `https://www.google.com/maps/search/?api=1&query=${state.location.lat},${state.location.lng}`;
    const addrText = formatAddress(state.location.address);

    let msg = `*Pedido - Las Tortas Del Chiche*\n`;
    msg += `\n`;
    msg += `*Sucursal:* ${BRANCHES[state.branch].name}`;
    msg += `\n*Cliente:* ${state.customer.name}`;
    msg += `\n*Tel:* ${state.customer.phone}`;
    msg += `\n*Referencia:* ${state.customer.addressRef}`;
    if (addrText) {
      msg += `\n*Dirección:* ${addrText}`;
    }
    msg += `\n*Ubicación:* ${mapsLink}`;
    msg += `\n`;
    msg += `\n-----------------------`;
    msg += `\n*Detalle del pedido:*\n`;
    state.items.forEach((item) => {
      msg += `*${item.quantity}x* ${item.name} — $${item.price * item.quantity}\n`;
      if (item.notes && item.notes.trim()) {
        msg += `   Nota: ${item.notes.trim()}\n`;
      }
    });
    msg += `-----------------------`;
    msg += `\n*Total: $${total} MXN*`;
    msg += `\n*Método de pago:* ${state.payment === "efectivo" ? "Efectivo" : "Transferencia"}`;

    const url = `https://wa.me/${BRANCHES[state.branch].whatsapp}?text=${encodeURIComponent(msg)}`;
    const sendBtn = document.getElementById("cart-send-whatsapp");
    if (sendBtn) {
      sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo WhatsApp...';
      sendBtn.disabled = true;
    }
    const opened = window.open(url, "_blank", "noopener,noreferrer");
    if (!opened) {
      navigator.clipboard.writeText(msg).then(() => {
        showCartAlert("No se pudo abrir WhatsApp. El mensaje se copió al portapapeles. Pégalo en WhatsApp manualmente.");
      }).catch(() => {
        showCartAlert("No se pudo abrir WhatsApp. Activa las ventanas emergentes e intenta de nuevo.");
      });
    }

    saveToHistory();

    state = { items: [], branch: "atasta", payment: "efectivo", customer: { name: "", phone: "", addressRef: "" }, location: { lat: null, lng: null, confirmed: false, address: null } };
    save();
    renderSidebar();
    renderBadge();
    closeSidebar();
    if (sendBtn) {
      sendBtn.innerHTML = '<i class="fab fa-whatsapp"></i> Enviar pedido por WhatsApp';
      sendBtn.disabled = false;
    }
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
  function init() {
    load();

    document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const card = btn.closest(".menu-item");
        if (!card) return;
        const id = Number(card.dataset.id);
        const name = card.dataset.name;
        const price = card.dataset.price;
        const img = card.dataset.img;
        addItem(id, name, price, img);

        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Agregado';
        btn.classList.add("added");
        setTimeout(() => {
          btn.innerHTML = original;
          btn.classList.remove("added");
        }, 1000);
      });
    });

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

  return { addItem, removeItem, openSidebar, closeSidebar };
})();
