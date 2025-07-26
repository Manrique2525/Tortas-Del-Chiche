    document.addEventListener('DOMContentLoaded', function() {
        // ======== Menú Hamburguesa ========
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        
        // Función para alternar el menú
        function toggleMenu() {
            navLinks.classList.toggle('show');
            const icon = hamburger.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
            
            // Bloquear el scroll cuando el menú está abierto
            document.body.style.overflow = navLinks.classList.contains('show') ? 'hidden' : 'auto';
        }
        
        // Evento para el botón hamburguesa
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', toggleMenu);
            
            // Cerrar el menú al hacer clic en un enlace
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', () => {
                    if (navLinks.classList.contains('show')) {
                        toggleMenu();
                    }
                });
            });
        }

        // ======== Smooth Scrolling ========
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

        // ======== Efecto de scroll para el header ========
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
            } else {
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        });

        // ======== Animación de elementos al hacer scroll ========
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

        // ======== Modal de Bienvenida ========
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

        // ======== Botones Flotantes ========
        setTimeout(function() {
            console.log('Creando botones flotantes...');

            const floatingHTML = `
                <div class="simple-floating-buttons" style="
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    z-index: 9999;
                ">
                    <div id="didi-btn" style="
                        width: 55px;
                        height: 55px;
                        background-color: #FF6B35;
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        font-weight: bold;
                        font-size: 14px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="img/didi.png" alt="Didi" style="width: 60%; height: 60%; object-fit: contain;" />
                    </div>

                    <div id="whatsapp-btn" style="
                        width: 55px;
                        height: 55px;
                        background-color: #25d366;
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        font-size: 26px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', floatingHTML);
            console.log('Botones insertados en el DOM');

            // Eventos para los botones flotantes
            const didiBtn = document.getElementById('didi-btn');
            const whatsappBtn = document.getElementById('whatsapp-btn');

            didiBtn?.addEventListener('click', () => {
                closeWhatsappModalIfExists();
                if (!document.getElementById('didi-modal')) {
                    showDidiModal();
                }
            });

            whatsappBtn?.addEventListener('click', () => {
                closeDidiModalIfExists();
                const whatsappModal = document.getElementById('whatsapp-modal');
                if (!whatsappModal) {
                    createWhatsappModal();
                }
                whatsappModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });

            // Funciones auxiliares para cerrar modales
            function closeDidiModalIfExists() {
                const modal = document.getElementById('didi-modal');
                if (modal) {
                    modal.remove();
                    document.body.style.overflow = 'auto';
                }
            }

            function closeWhatsappModalIfExists() {
                const modal = document.getElementById('whatsapp-modal');
                if (modal && modal.style.display !== 'none') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            }
        }, 1000);

        // ======== Datos de Sucursales Didi ========
        const didiBranches = [
            {
                name: "Sucursal Atasta",
                horario: { apertura: 7.0, cierre: 14.0 },
                img: "img/logo.jpeg",
                desc: "Cochinita Pibil",
                url: "https://www.didi-food.com/es-MX/food/store/5764607750370494503/LAS-TORTAS-DEL-CHICHE"
            },
            {
                name: "Sucursal Centro",
                horario: { apertura: 7.0, cierre: 14.0 },
                img: "img/logo.jpeg",
                desc: "Cochinita Pibil",
                url: "https://www.didi-food.com/es-MX/food/store/5764608505894668049/Las-Tortas-del-Chiche-(Suc-Lino-Merino)/"
            },
            {
                name: "Dog Burger",
                horario: { apertura: 17.5, cierre: 22.75 },
                img: "img/logo_dogo.jpeg",
                desc: "5:30pm - 10:45pm",
                url: "https://www.didi-food.com/es-MX/food/store/5764608374411624670/DOG-BURGUER-DEL-CHICHE"
            }
        ];

        // ======== Funciones para Modal Didi ========
        function horaDecimal() {
            const ahora = new Date();
            return ahora.getHours() + ahora.getMinutes() / 60;
        }

        function estaAbierto(horario, horaActual) {
            return horaActual >= horario.apertura && horaActual < horario.cierre;
        }

        function formatHora(h) {
            let horas = Math.floor(h);
            let minutos = Math.round((h - horas) * 60);
            let ampm = horas >= 12 ? 'pm' : 'am';
            horas = horas % 12;
            if (horas === 0) horas = 12;
            return `${horas}:${minutos.toString().padStart(2, '0')} ${ampm}`;
        }

        function showDidiModal() {
            const hora = horaDecimal();

            if (document.getElementById('didi-modal')) return;

            const modal = document.createElement('div');
            modal.id = 'didi-modal';
            modal.className = 'modal-overlay';
            modal.style.cssText = `
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                align-items: center;
                justify-content: center;
                padding: 15px;
                box-sizing: border-box;
            `;

            const container = document.createElement('div');
            container.className = 'modal-container whatsapp-container';
            container.style.cssText = `
                background: white;
                border-radius: 15px;
                position: relative;
                width: 100%;
                max-width: 500px;
                max-height: 85vh;
                overflow-y: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            `;

            const content = document.createElement('div');
            content.className = 'modal-content';
            content.style.cssText = `
                padding: 20px 15px 15px 15px;
            `;

            const title = document.createElement('h3');
            title.textContent = 'Didi Food';
            title.style.cssText = `
                text-align: center;
                color: #FF6B35;
                margin: 0 0 15px 0;
                font-size: clamp(1.1rem, 4vw, 1.3rem);
                font-weight: 600;
            `;

            const options = document.createElement('div');
            options.className = 'whatsapp-options';
            options.style.cssText = `
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            `;

            didiBranches.forEach(suc => {
                const abierto = estaAbierto(suc.horario, hora);
                
                const optionDiv = document.createElement('div');
                optionDiv.className = 'whatsapp-option';
                optionDiv.style.cssText = `
                    background: #f8f9fa;
                    padding: 12px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                    border: 2px solid transparent;
                `;

                optionDiv.innerHTML = `
                    <img src="${suc.img}" alt="${suc.name}" style="
                        width: 45px;
                        height: 45px;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 2px solid #FF6B35;
                        flex-shrink: 0;
                    ">
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="
                            color: #333;
                            margin: 0 0 3px 0;
                            font-size: clamp(0.9rem, 3.5vw, 1rem);
                            font-weight: 600;
                        ">${suc.name}</h4>
                        <p style="
                            color: #666;
                            margin: 0;
                            font-size: clamp(0.75rem, 3vw, 0.85rem);
                            line-height: 1.2;
                        ">${suc.desc}</p>
                    </div>
                    <div style="flex-shrink: 0;">
                        ${abierto ? `
                            <a href="${suc.url}" target="_blank" style="
                                background: #FF6B35;
                                color: white;
                                padding: 8px 12px;
                                border-radius: 6px;
                                text-decoration: none;
                                font-size: clamp(0.75rem, 3vw, 0.85rem);
                                font-weight: 500;
                                display: inline-block;
                                transition: all 0.3s ease;
                            ">Ordenar</a>
                        ` : `
                            <button disabled style="
                                background: #ccc;
                                color: #666;
                                padding: 6px 10px;
                                border-radius: 6px;
                                border: none;
                                font-size: clamp(0.65rem, 2.5vw, 0.75rem);
                                cursor: not-allowed;
                                text-align: center;
                                line-height: 1.2;
                            ">
                                ${formatHora(suc.horario.apertura)} - ${formatHora(suc.horario.cierre)}
                            </button>
                        `}
                    </div>
                `;

                options.appendChild(optionDiv);
            });

            const closeBtn = document.createElement('button');
            closeBtn.className = 'modal-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = `
                position: absolute;
                top: 8px;
                right: 8px;
                background-color: #FF6B35;
                border: none;
                color: white;
                cursor: pointer;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                transition: all 0.3s ease;
                font-size: 18px;
                font-weight: bold;
                z-index: 1;
            `;

            closeBtn.addEventListener('click', closeDidiModal);

            function closeDidiModal() {
                modal.remove();
                document.body.style.overflow = 'auto';
            }

            content.appendChild(title);
            content.appendChild(options);
            container.appendChild(content);
            container.appendChild(closeBtn);
            modal.appendChild(container);
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';

            // Hover effects
            options.querySelectorAll('.whatsapp-option').forEach(option => {
                option.addEventListener('mouseenter', () => {
                    option.style.transform = 'translateY(-2px)';
                    option.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    option.style.borderColor = '#FF6B35';
                });
                option.addEventListener('mouseleave', () => {
                    option.style.transform = 'translateY(0)';
                    option.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                    option.style.borderColor = 'transparent';
                });
            });

            modal.addEventListener('click', e => {
                if (e.target === modal) closeDidiModal();
            });

            document.addEventListener('keydown', function escClose(e) {
                if (e.key === 'Escape') {
                    closeDidiModal();
                    document.removeEventListener('keydown', escClose);
                }
            });
        }

        // ======== Modal WhatsApp con Validación de Horarios ========
        function createWhatsappModal() {
            // Definir datos de sucursales con horarios
            const whatsappBranches = [
                {
                    name: "Sucursal Atasta",
                    address: "Av. 27 de Febrero #2616",
                    horario: { apertura: 7.0, cierre: 14.0 },
                    img: "img/logo.jpeg",
                    whatsapp: "529933092124"
                },
                {
                    name: "Sucursal Centro",
                    address: "Lino Merino #831",
                    horario: { apertura: 7.0, cierre: 14.0 },
                    img: "img/logo.jpeg",
                    whatsapp: "529932206325"
                },
                {
                    name: "Dog Burger",
                    address: "Hot Dogs & Hamburguesas",
                    horario: { apertura: 17.5, cierre: 22.75 },
                    img: "img/logo_dogo.jpeg",
                    whatsapp: "529933092124"
                }
            ];

            const hora = horaDecimal();
            
            const whatsappModal = document.createElement('div');
            whatsappModal.id = 'whatsapp-modal';
            whatsappModal.className = 'modal-overlay whatsapp-modal';
            whatsappModal.style.cssText = `
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                align-items: center;
                justify-content: center;
                padding: 15px;
                box-sizing: border-box;
            `;

            const whatsappContainer = document.createElement('div');
            whatsappContainer.className = 'modal-container whatsapp-container';
            whatsappContainer.style.cssText = `
                background: white;
                border-radius: 15px;
                position: relative;
                width: 100%;
                max-width: 500px;
                max-height: 85vh;
                overflow-y: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            `;

            const whatsappContent = document.createElement('div');
            whatsappContent.className = 'modal-content';
            whatsappContent.style.cssText = `
                padding: 20px 15px 15px 15px;
            `;

            const whatsappTitle = document.createElement('h3');
            whatsappTitle.textContent = 'WhatsApp';
            whatsappTitle.style.cssText = `
                text-align: center;
                color: #FF6B35;
                margin: 0 0 15px 0;
                font-size: clamp(1.1rem, 4vw, 1.3rem);
                font-weight: 600;
            `;

            const whatsappOptions = document.createElement('div');
            whatsappOptions.className = 'whatsapp-options';
            whatsappOptions.style.cssText = `
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-top: 10px;
            `;

            // Generar opciones dinámicamente con validación de horarios
            whatsappBranches.forEach(sucursal => {
                const abierto = estaAbierto(sucursal.horario, hora);
                
                const option = document.createElement('div');
                option.className = 'whatsapp-option';
                option.style.cssText = `
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                    border: 2px solid transparent;
                    ${!abierto ? 'opacity: 0.7;' : ''}
                `;

                option.innerHTML = `
                    <img src="${sucursal.img}" alt="${sucursal.name}" style="
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        object-fit: cover;
                        border: 2px solid #FF6B35;
                        flex-shrink: 0;
                        ${!abierto ? 'filter: grayscale(0.5);' : ''}
                    ">
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="
                            color: #333;
                            margin: 0 0 4px 0;
                            font-size: clamp(0.9rem, 3.5vw, 1rem);
                            font-weight: 600;
                        ">${sucursal.name}</h4>
                        <p style="
                            color: #666;
                            margin: 0 0 2px 0;
                            font-size: clamp(0.75rem, 3vw, 0.85rem);
                            line-height: 1.3;
                        ">${sucursal.address}</p>
                        <small style="
                            color: ${abierto ? '#4CAF50' : '#888'};
                            font-size: clamp(0.7rem, 2.8vw, 0.8rem);
                            font-weight: ${abierto ? '600' : '400'};
                        ">
                            ${abierto ? 'ABIERTO' : 'CERRADO'} • ${formatHora(sucursal.horario.apertura)} - ${formatHora(sucursal.horario.cierre)}
                        </small>
                    </div>
                    <div style="flex-shrink: 0;">
                        ${abierto ? `
                            <a href="https://wa.me/${sucursal.whatsapp}" target="_blank" style="
                                background: #25d366;
                                color: white;
                                padding: 10px 15px;
                                border-radius: 8px;
                                text-decoration: none;
                                font-size: clamp(0.8rem, 3vw, 0.9rem);
                                font-weight: 500;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                transition: all 0.3s ease;
                            ">
                                <i class="fab fa-whatsapp" style="font-size: 1.1em;"></i>
                                Ordenar
                            </a>
                        ` : `
                            <button disabled style="
                                background: #ccc;
                                color: #666;
                                padding: 8px 12px;
                                border-radius: 8px;
                                border: none;
                                font-size: clamp(0.7rem, 2.8vw, 0.8rem);
                                cursor: not-allowed;
                                text-align: center;
                                line-height: 1.2;
                                font-weight: 500;
                            ">
                                Cerrado
                            </button>
                        `}
                    </div>
                `;

                // Solo agregar efectos hover si está abierto
                if (abierto) {
                    option.addEventListener('mouseenter', () => {
                        option.style.transform = 'translateY(-2px)';
                        option.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                        option.style.borderColor = '#25d366';
                    });
                    
                    option.addEventListener('mouseleave', () => {
                        option.style.transform = 'translateY(0)';
                        option.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                        option.style.borderColor = 'transparent';
                    });
                }

                whatsappOptions.appendChild(option);
            });

            // Botón de cerrar
            const whatsappCloseBtn = document.createElement('button');
            whatsappCloseBtn.className = 'modal-close';
            whatsappCloseBtn.innerHTML = '&times;';
            whatsappCloseBtn.style.cssText = `
                position: absolute;
                top: 8px;
                right: 8px;
                background-color: #FF6B35;
                border: none;
                color: white;
                cursor: pointer;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                transition: all 0.3s ease;
                font-size: 18px;
                font-weight: bold;
                z-index: 1;
            `;

            function closeWhatsappModal() {
                whatsappModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            whatsappCloseBtn.addEventListener('click', closeWhatsappModal);

            whatsappContent.appendChild(whatsappTitle);
            whatsappContent.appendChild(whatsappOptions);
            whatsappContainer.appendChild(whatsappContent);
            whatsappContainer.appendChild(whatsappCloseBtn);
            whatsappModal.appendChild(whatsappContainer);
            document.body.appendChild(whatsappModal);

            // Cerrar al hacer clic fuera del modal
            whatsappModal.addEventListener('click', function(e) {
                if (e.target === whatsappModal) {
                    closeWhatsappModal();
                }
            });

            // Cerrar con tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && whatsappModal.style.display === 'flex') {
                    closeWhatsappModal();
                }
            });
        }
    });