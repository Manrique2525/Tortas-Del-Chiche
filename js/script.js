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
});
