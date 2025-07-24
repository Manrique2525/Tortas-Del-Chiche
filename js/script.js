document.addEventListener('DOMContentLoaded', function() {
    // Menú hamburguesa para móviles
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    
    hamburger.addEventListener('click', function() {
        navLinks.classList.toggle('show');
    });

    // Cerrar menú al hacer clic en un enlace
    const navItems = document.querySelectorAll('.nav-links a');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navLinks.classList.remove('show');
            }
        });
    });

    // Smooth scrolling para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Efecto de scroll para el header
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 100) {
            header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
        } else {
            header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        }
    });

    // Animación de elementos al hacer scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.menu-item, .gallery-item, .about-image img');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };

    // Establecer propiedades iniciales para la animación
    const animatedElements = document.querySelectorAll('.menu-item, .gallery-item, .about-image img');
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    // Ejecutar al cargar y al hacer scroll
    animateOnScroll();
    window.addEventListener('scroll', animateOnScroll);

    // Modal de bienvenida
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    
    const modalContainer = document.createElement('div');
    modalContainer.className = 'modal-container';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    
    const modalImage = document.createElement('img');
    modalImage.src = 'img/logo_largo.jpeg';
    modalImage.alt = 'Las Tortas Del Chiche';
    modalImage.className = 'modal-image';
    
    const closeButton = document.createElement('button');
    closeButton.className = 'modal-close';
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Cerrar');

    modalContent.appendChild(modalImage);
    modalContainer.appendChild(modalContent);
    modalContainer.appendChild(closeButton);
    modalOverlay.appendChild(modalContainer);
    document.body.appendChild(modalOverlay);

    setTimeout(function() {
        modalOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        setTimeout(function() {
            closeModal();
        }, 8000);
    }, 800);

    function closeModal() {
        modalOverlay.style.opacity = '0';
        setTimeout(function() {
            modalOverlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300);
    }

    closeButton.addEventListener('click', closeModal);

    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Botón flotante de WhatsApp con selección de sucursal
    const whatsappFloat = document.createElement('div');
    whatsappFloat.className = 'whatsapp-float';
    whatsappFloat.innerHTML = '<i class="fab fa-whatsapp"></i>';
    whatsappFloat.setAttribute('aria-label', 'Ordenar por WhatsApp');
    document.body.appendChild(whatsappFloat);

    // Modal de selección de sucursal para WhatsApp
    const whatsappModal = document.createElement('div');
    whatsappModal.id = 'whatsapp-modal';
    whatsappModal.className = 'modal-overlay whatsapp-modal';
    whatsappModal.style.display = 'none';
    
    const whatsappContainer = document.createElement('div');
    whatsappContainer.className = 'modal-container whatsapp-container';
    
    const whatsappContent = document.createElement('div');
    whatsappContent.className = 'modal-content';
    
    const whatsappTitle = document.createElement('h3');
    whatsappTitle.textContent = 'Selecciona una sucursal para ordenar';
    
    const whatsappOptions = document.createElement('div');
    whatsappOptions.className = 'whatsapp-options';
    
    // Opción para sucursal Atasta
    const optionAtasta = document.createElement('div');
    optionAtasta.className = 'whatsapp-option';
    optionAtasta.innerHTML = `
        <img src="img/logo.jpeg" alt="Sucursal Atasta">
        <h4>Sucursal Atasta</h4>
        <p>Av. 27 de Febrero #2616</p>
        <a href="https://wa.me/529933092124" class="btn" target="_blank">
            <i class="fab fa-whatsapp"></i> Ordenar
        </a>
    `;
    
    // Opción para sucursal Centro
    const optionCentro = document.createElement('div');
    optionCentro.className = 'whatsapp-option';
    optionCentro.innerHTML = `
        <img src="img/logo.jpeg" alt="Sucursal Centro">
        <h4>Sucursal Centro</h4>
        <p>Lino Merino #831</p>
        <a href="https://wa.me/529932206325" class="btn" target="_blank">
            <i class="fab fa-whatsapp"></i> Ordenar
        </a>
    `;
    
    const whatsappCloseBtn = document.createElement('button');
    whatsappCloseBtn.className = 'modal-close whatsapp-close';
    whatsappCloseBtn.innerHTML = '&times;';
    whatsappCloseBtn.setAttribute('aria-label', 'Cerrar');

    whatsappOptions.appendChild(optionAtasta);
    whatsappOptions.appendChild(optionCentro);
    whatsappContent.appendChild(whatsappTitle);
    whatsappContent.appendChild(whatsappOptions);
    whatsappContainer.appendChild(whatsappContent);
    whatsappContainer.appendChild(whatsappCloseBtn);
    whatsappModal.appendChild(whatsappContainer);
    document.body.appendChild(whatsappModal);

    // Mostrar modal al hacer clic en el botón flotante
    whatsappFloat.addEventListener('click', function(e) {
        e.preventDefault();
        whatsappModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    // Cerrar modal de WhatsApp
    function closeWhatsappModal() {
        whatsappModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    whatsappCloseBtn.addEventListener('click', closeWhatsappModal);

    whatsappModal.addEventListener('click', function(e) {
        if (e.target === whatsappModal) {
            closeWhatsappModal();
        }
    });

    // Cerrar ambos modales con la tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeWhatsappModal();
        }
    });

    // Mejorar accesibilidad para modales
    const modals = [modalOverlay, whatsappModal];
    modals.forEach(modal => {
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
    });

    // Enfoque en el modal cuando se abre
    whatsappFloat.addEventListener('click', function() {
        whatsappModal.setAttribute('aria-hidden', 'false');
        whatsappCloseBtn.focus();
    });

    whatsappCloseBtn.addEventListener('click', function() {
        whatsappModal.setAttribute('aria-hidden', 'true');
        whatsappFloat.focus();
    });
});