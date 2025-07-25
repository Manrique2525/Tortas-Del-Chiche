// ======== Menú Hamburguesa ========
document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar elementos del DOM
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

    // ======== Botones Flotantes ========
    setTimeout(function() {
        console.log('Creando botones flotantes...');

        const floatingHTML = `
            <div class="simple-floating-buttons" style="
                position: fixed;
                bottom: 30px;
                right: 30px;
                display: flex;
                flex-direction: column;
                gap: 15px;
                z-index: 9999;
            ">
                <div id="didi-btn" style="
                    width: 60px;
                    height: 60px;
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
                    width: 60px;
                    height: 60px;
                    background-color: #25d366;
                    color: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    font-size: 30px;
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
        modal.style.display = 'flex';

        const container = document.createElement('div');
        container.className = 'modal-container whatsapp-container';
        container.style.maxWidth = '600px';
        container.style.width = '90%';

        const content = document.createElement('div');
        content.className = 'modal-content';

        const title = document.createElement('h3');
        title.textContent = 'Selecciona una opción para Didi Food';
        title.style.textAlign = 'center';
        title.style.color = '#FF6B35';
        title.style.marginBottom = '20px';
        title.style.fontSize = '1.3rem';

        const options = document.createElement('div');
        options.className = 'whatsapp-options';
        options.style.display = 'grid';
        options.style.gridTemplateColumns = 'repeat(auto-fit, minmax(250px, 1fr))';
        options.style.gap = '15px';
        options.style.marginTop = '15px';

        didiBranches.forEach(suc => {
            const abierto = estaAbierto(suc.horario, hora);

            options.innerHTML += `
                <div class="whatsapp-option" style="
                    background: white;
                    padding: 15px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                    transition: transform 0.3s ease;
                    font-size: 0.9rem;
                ">
                    <img src="${suc.img}" alt="${suc.name}" style="
                        width: 60px;
                        height: 60px;
                        border-radius: 50%;
                        object-fit: cover;
                        margin-bottom: 10px;
                        border: 3px solid #FF6B35;
                    ">
                    <h4 style="color: var(--primary-black); margin-bottom: 5px;">${suc.name}</h4>
                    <p style="color: var(--text-color); margin-bottom: 10px;">${suc.desc}</p>
                    ${abierto ? `
                        <a href="${suc.url}" target="_blank" class="btn" style="background: #FF6B35; padding: 7px 14px; font-size: 0.9rem;">
                            Ordenar en Didi
                        </a>
                    ` : `
                        <button disabled class="btn" style="background: #ccc; padding: 7px 14px; font-size: 0.9rem; color: #666; cursor: not-allowed;">
                            Abierto de ${formatHora(suc.horario.apertura)} a ${formatHora(suc.horario.cierre)}
                        </button>
                    `}
                </div>
            `;
        });

        const closeBtn = document.createElement('button');
        closeBtn.className = 'modal-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '-15px';
        closeBtn.style.right = '-15px';
        closeBtn.style.backgroundColor = '#FF6B35';
        closeBtn.style.border = '2px solid white';
        closeBtn.style.color = 'white';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.width = '35px';
        closeBtn.style.height = '35px';
        closeBtn.style.borderRadius = '50%';
        closeBtn.style.display = 'flex';
        closeBtn.style.justifyContent = 'center';
        closeBtn.style.alignItems = 'center';
        closeBtn.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)';
        closeBtn.style.transition = 'all 0.3s ease';
        closeBtn.setAttribute('aria-label', 'Cerrar');

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

        // Hover effect
        options.querySelectorAll('.whatsapp-option').forEach(option => {
            option.addEventListener('mouseenter', () => {
                option.style.transform = 'translateY(-5px)';
                option.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
            });
            option.addEventListener('mouseleave', () => {
                option.style.transform = 'translateY(0)';
                option.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)';
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

    // ======== Modal WhatsApp ========
  // ======== Modal WhatsApp (con diseño similar a Didi) ========
function createWhatsappModal() {
    const whatsappModal = document.createElement('div');
    whatsappModal.id = 'whatsapp-modal';
    whatsappModal.className = 'modal-overlay whatsapp-modal';
    whatsappModal.style.display = 'none';

    const whatsappContainer = document.createElement('div');
    whatsappContainer.className = 'modal-container whatsapp-container';
    whatsappContainer.style.maxWidth = '600px';
    whatsappContainer.style.width = '90%';

    const whatsappContent = document.createElement('div');
    whatsappContent.className = 'modal-content';

    const whatsappTitle = document.createElement('h3');
    whatsappTitle.textContent = 'Selecciona una sucursal para ordenar por WhatsApp';
    whatsappTitle.style.textAlign = 'center';
    whatsappTitle.style.color = '#FF6B35'; // Naranja como Didi
    whatsappTitle.style.marginBottom = '20px';
    whatsappTitle.style.fontSize = '1.3rem';

    const whatsappOptions = document.createElement('div');
    whatsappOptions.className = 'whatsapp-options';
    whatsappOptions.style.display = 'grid';
    whatsappOptions.style.gridTemplateColumns = 'repeat(auto-fit, minmax(250px, 1fr))';
    whatsappOptions.style.gap = '15px';
    whatsappOptions.style.marginTop = '15px';

    // Sucursal Atasta
    const optionAtasta = document.createElement('div');
    optionAtasta.className = 'whatsapp-option';
    optionAtasta.style.background = 'white';
    optionAtasta.style.padding = '20px';
    optionAtasta.style.borderRadius = '10px';
    optionAtasta.style.textAlign = 'center';
    optionAtasta.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)';
    optionAtasta.style.transition = 'all 0.3s ease';
    optionAtasta.innerHTML = `
        <img src="img/logo.jpeg" alt="Sucursal Atasta" style="
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #FF6B35;  // Naranja como Didi
        ">
        <h4 style="color: var(--primary-black); margin-bottom: 5px; font-size: 1.1rem;">Sucursal Atasta</h4>
        <p style="color: var(--text-color); margin-bottom: 15px; font-size: 0.9rem;">
            Av. 27 de Febrero #2616<br>
            <small>7:00 am - 2:00 pm</small>
        </p>
        <a href="https://wa.me/529933092124" class="btn" target="_blank" style="
            background: #FF6B35;  // Naranja como Didi
            padding: 8px 15px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        ">
            <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Ordenar
        </a>
    `;

    // Sucursal Centro
    const optionCentro = document.createElement('div');
    optionCentro.className = 'whatsapp-option';
    optionCentro.style.background = 'white';
    optionCentro.style.padding = '20px';
    optionCentro.style.borderRadius = '10px';
    optionCentro.style.textAlign = 'center';
    optionCentro.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)';
    optionCentro.style.transition = 'all 0.3s ease';
    optionCentro.innerHTML = `
        <img src="img/logo.jpeg" alt="Sucursal Centro" style="
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #FF6B35;  // Naranja como Didi
        ">
        <h4 style="color: var(--primary-black); margin-bottom: 5px; font-size: 1.1rem;">Sucursal Centro</h4>
        <p style="color: var(--text-color); margin-bottom: 15px; font-size: 0.9rem;">
            Lino Merino #831<br>
            <small>7:00 am - 2:00 pm</small>
        </p>
        <a href="https://wa.me/529932206325" class="btn" target="_blank" style="
            background: #FF6B35;  // Naranja como Didi
            padding: 8px 15px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        ">
            <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Ordenar
        </a>
    `;

    // Botón de cerrar (naranja como Didi)
    const whatsappCloseBtn = document.createElement('button');
    whatsappCloseBtn.className = 'modal-close';
    whatsappCloseBtn.innerHTML = '&times;';
    whatsappCloseBtn.style.position = 'absolute';
    whatsappCloseBtn.style.top = '-15px';
    whatsappCloseBtn.style.right = '-15px';
    whatsappCloseBtn.style.backgroundColor = '#FF6B35';  // Naranja como Didi
    whatsappCloseBtn.style.border = '2px solid white';
    whatsappCloseBtn.style.color = 'white';
    whatsappCloseBtn.style.cursor = 'pointer';
    whatsappCloseBtn.style.width = '35px';
    whatsappCloseBtn.style.height = '35px';
    whatsappCloseBtn.style.borderRadius = '50%';
    whatsappCloseBtn.style.display = 'flex';
    whatsappCloseBtn.style.justifyContent = 'center';
    whatsappCloseBtn.style.alignItems = 'center';
    whatsappCloseBtn.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)';
    whatsappCloseBtn.style.transition = 'all 0.3s ease';
    whatsappCloseBtn.setAttribute('aria-label', 'Cerrar');

    function closeWhatsappModal() {
        whatsappModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    whatsappCloseBtn.addEventListener('click', closeWhatsappModal);

    // Efectos hover para las opciones
    [optionAtasta, optionCentro].forEach(option => {
        option.addEventListener('mouseenter', () => {
            option.style.transform = 'translateY(-5px)';
            option.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
        });
        
        option.addEventListener('mouseleave', () => {
            option.style.transform = 'translateY(0)';
            option.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)';
        });
    });

    whatsappOptions.appendChild(optionAtasta);
    whatsappOptions.appendChild(optionCentro);
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