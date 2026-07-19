const PRODUCTS_API_URL = '/api/products';

let productsCache = [];

function createProductCard(product) {
    const div = document.createElement('div');
    const isInactive = product.active === false || product.active === 0;
    div.className = isInactive ? 'menu-item menu-item-inactive' : 'menu-item';
    div.dataset.id = product.id;
    div.dataset.name = product.name;
    div.dataset.price = product.price;
    div.dataset.img = product.image || '';
    if (isInactive) div.dataset.inactive = '1';

    const hasMojado = product.has_mojado === true || product.has_mojado === 1;
    const hasSeco = product.has_seco === true || product.has_seco === 1;
    const hasCochinita = product.has_cochinita === true || product.has_cochinita === 1;
    const hasLechon = product.has_lechon === true || product.has_lechon === 1;

    const hasTypeOptions = hasMojado || hasSeco;
    const hasMeatOptions = hasCochinita || hasLechon;
    const needsOptions = hasTypeOptions || hasMeatOptions;

    const description = product.description || '';

    let optionsHtml = '';
    if (hasTypeOptions) {
        let typeOpts = '';
        if (hasMojado) typeOpts += '<option value="mojado">Mojado</option>';
        if (hasSeco) typeOpts += '<option value="seco">Seco</option>';
        optionsHtml += `
            <div class="product-option-group" data-option="type">
                <span class="product-option-label">Tipo:</span>
                <select class="product-option-select">
                    <option value="">Seleccionar...</option>
                    ${typeOpts}
                </select>
            </div>
        `;
    }
    if (hasMeatOptions) {
        let meatOpts = '';
        if (hasCochinita) meatOpts += '<option value="cochinita">Cochinita</option>';
        if (hasLechon) meatOpts += '<option value="lechon">Lechón</option>';
        optionsHtml += `
            <div class="product-option-group" data-option="meat">
                <span class="product-option-label">Carne:</span>
                <select class="product-option-select">
                    <option value="">Seleccionar...</option>
                    ${meatOpts}
                </select>
            </div>
        `;
    }

    const addBtnDisabled = needsOptions ? ' disabled' : '';

    div.innerHTML = `
        <div class="menu-item-image-wrapper">
            <img src="${product.image}" alt="${product.name}" loading="lazy"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22250%22><rect width=%22400%22 height=%22250%22 fill=%22%23FF6B35%22/><text x=%22200%22 y=%22130%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2224%22 font-family=%22sans-serif%22>${encodeURIComponent(product.name)}</text></svg>'" />
            ${isInactive ? '<span class="unavailable-badge">No Disponible</span>' : ''}
        </div>
        <div class="menu-item-header">
            <h3>${product.name}</h3>
            <span class="price">$${product.price}</span>
        </div>
        ${description ? `<p>${description}</p>` : ''}
        ${!isInactive && needsOptions ? optionsHtml : ''}
        ${isInactive
            ? `<button class="add-to-cart-btn unavailable-btn" disabled aria-label="${product.name} no disponible">
                <i class="fas fa-ban"></i> No Disponible
               </button>`
            : `<button class="add-to-cart-btn${addBtnDisabled}" aria-label="Agregar ${product.name} al carrito"${addBtnDisabled}>
                <i class="fas fa-cart-plus"></i> Agregar
               </button>`
        }
    `;

    if (needsOptions && !isInactive) {
        div.dataset.hasTypeOptions = hasTypeOptions ? '1' : '0';
        div.dataset.hasMeatOptions = hasMeatOptions ? '1' : '0';
        div.querySelectorAll('.product-option-select').forEach(function(sel) {
            sel.addEventListener('change', function() {
                updateAddButtonState(div);
            });
        });
    }

    return div;
}

function getProductOptions(card) {
    const options = {};
    const typeSelect = card.querySelector('[data-option="type"] .product-option-select');
    const meatSelect = card.querySelector('[data-option="meat"] .product-option-select');
    if (typeSelect && typeSelect.value) options.type = typeSelect.value;
    if (meatSelect && meatSelect.value) options.meat = meatSelect.value;
    return options;
}

function updateAddButtonState(card) {
    const btn = card.querySelector('.add-to-cart-btn');
    if (!btn || btn.disabled === undefined) return;
    const options = getProductOptions(card);
    const hasTypeOptions = card.dataset.hasTypeOptions === '1';
    const hasMeatOptions = card.dataset.hasMeatOptions === '1';
    const allSelected = (!hasTypeOptions || options.type) && (!hasMeatOptions || options.meat);
    btn.disabled = !allSelected;
    if (allSelected) {
        btn.classList.remove('disabled');
    } else {
        btn.classList.add('disabled');
    }
}

async function loadProducts() {
    try {
        const res = await fetch(PRODUCTS_API_URL);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const products = await res.json();
        productsCache = products;

        const comidaGrid = document.getElementById('comida-grid');
        const bebidasGrid = document.getElementById('bebidas-grid');

        if (comidaGrid) comidaGrid.innerHTML = '';
        if (bebidasGrid) bebidasGrid.innerHTML = '';

        const comidas = products.filter(p => p.category === 'comida');
        const bebidas = products.filter(p => p.category === 'bebida');

        if (comidas.length === 0 && comidaGrid) {
            comidaGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No hay productos disponibles</div>';
        }

        if (bebidas.length === 0 && bebidasGrid) {
            bebidasGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">No hay bebidas disponibles</div>';
        }

        comidas.forEach(product => {
            if (comidaGrid) comidaGrid.appendChild(createProductCard(product));
        });

        bebidas.forEach(product => {
            if (bebidasGrid) bebidasGrid.appendChild(createProductCard(product));
        });

        if (typeof initCartAddButtons === 'function') {
            initCartAddButtons();
        }
        if (typeof syncAllCardButtons === 'function') {
            syncAllCardButtons();
        }

        initScrollAnimations();

    } catch (error) {
        console.error('Error cargando productos:', error);
        const comidaGrid = document.getElementById('comida-grid');
        const bebidasGrid = document.getElementById('bebidas-grid');
        if (comidaGrid) comidaGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#c62828;"><i class="fas fa-exclamation-triangle"></i> Error cargando menú. Recarga la página.</div>';
        if (bebidasGrid) bebidasGrid.innerHTML = '';
    }
}

function initScrollAnimations() {
    const items = document.querySelectorAll('.menu-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = entry.target.classList.contains('menu-item-inactive') ? '0.6' : '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    items.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(item);
    });
}

function getProductOptionsByCard(card) {
    return getProductOptions(card);
}

document.addEventListener('DOMContentLoaded', loadProducts);
