# ANEXO A — ALCANCE FUNCIONAL DEL SISTEMA

| Campo | Dato |
|---|---|
| Sistema | Las Tortas Del Chiche |
| Dominio | lastortasdelchiche.com |
| Fecha | 21 de julio de 2026 |
| Versión | 1.1 |

---

## 1. FUNCIONALIDADES PREEXISTENTES

Las siguientes funcionalidades **ya estaban operativas** en el sitio web del CLIENTE antes de la firma de este contrato y **no forman parte del alcance de desarrollo** de este acuerdo:

- Página principal con presentación del negocio, logo, branding y catálogo de productos con imágenes, precios y descripciones.
- Filtrado de productos por categoría (Comida, Bebida).
- Selector de sucursal activa.
- Indicador visual de horario de atención (abierto/cerrado).
- Menú de navegación responsive (se adapta a celular y escritorio).
- Mapa interactivo de ubicación de las 2 sucursales (Atasta y AV Universidad) con enlaces a Google Maps.
- Enlace directo a Didi Food para cada sucursal.
- Envío de pedidos por WhatsApp con datos del cliente y lista de productos.
- Compatibilidad con dispositivos móviles (responsive).
- Instalable como aplicación en pantalla de inicio (PWA).
- Restricción de pedidos dentro del horario de atención (7:00 AM a 2:00 PM).
- Modo nocturno automático (8:00 PM a 6:00 AM).

---

## 2. FUNCIONALIDADES NUEVAS — DESARROLLADAS BAJO ESTE CONTRATO

Las siguientes funcionalidades fueron diseñadas, desarrolladas y entregadas por el PROVEEDOR bajo el presente contrato:

---

### 2.1 Carrito de Compras

- Selección de productos con opciones de personalización (mojado/seco, cochinita/lechón).
- Control de cantidad por producto (sumar/restar).
- Cálculo automático de subtotal.
- Opción de recoger en sucursal o envío a domicilio.
- Cálculo de costo de envío según distancia (geolocalización del cliente).
- Aplicación de cupones de descuento con validación automática.
- Selección de método de pago:
  - Efectivo contra entrega.
  - Transferencia bancaria (con datos de cuenta).
  - Tarjeta de crédito o débito vía MercadoPago.

---

### 2.2 Checkout y Envío del Pedido

- Formulario de datos del cliente (nombre, teléfono, dirección).
- Botón de geolocalización para detectar ubicación del cliente.
- Resumen completo del pedido antes de confirmar.
- Envío automático del pedido por WhatsApp con detalle completo (datos del cliente, productos, subtotal, envío, descuento, total, dirección, método de pago).
- Redirección al checkout de MercadoPago para pago con tarjeta.
- Almacenamiento local del historial de pedidos del cliente.

---

### 2.3 Integración con MercadoPago

- Checkout seguro con tarjeta de crédito o débito.
- Confirmación automática de pago recibido.
- Notificación automática de pago rechazado o cancelado.
- Manejo automático de reembolsos.
- Las preferencias de pago expiran a los 30 minutos.

---

### 2.4 Panel de Administración Web

#### 2.4.1 Inicio de sesión

- Acceso seguro con correo electrónico y contraseña.
- Sesión que expira automáticamente tras 8 horas de inactividad.
- Protección contra intentos de acceso múltiples.

#### 2.4.2 Panel principal (Dashboard)

- Resumen del día: cantidad de pedidos, ingresos, costos de envío.
- Total de pedidos pendientes por atender.
- Total de productos activos en el catálogo.
- Lista de los últimos 10 pedidos recibidos con estatus.
- Atajos de navegación a módulos principales.
- Alerta sonora cuando se recibe un pedido nuevo.
- Actualización automática de datos cada 30 segundos.

---

### 2.5 Gestión de Productos

- Alta de productos con: nombre, precio, descripción, imagen, categoría (Comida o Bebida).
- Edición de todos los campos de un producto.
- Eliminación de productos (se remueve imagen asociada).
- Activación o desactivación de productos (oculta del catálogo público sin eliminar).
- Control del orden de aparecimiento en el menú.
- Imágenes con formato controlado (JPEG, PNG, WebP).
- Opciones de personalización por producto:
  - Tipo de preparación (mojado, seco).
  - Tipo de carne (cochinita, lechón).

---

### 2.6 Gestión de Pedidos

- Visualización de todos los pedidos recibidos en una lista.
- Cambio de estatus con un clic:
  - Pendiente.
  - Aceptado.
  - En preparación.
  - Entregado.
  - Pagado.
  - Cancelado.
  - Reembolsado.
- Filtrado de pedidos por:
  - Estatus.
  - Sucursal.
  - Búsqueda por nombre o teléfono del cliente.
  - Rango de fechas.
- Estadísticas diarias:
  - Total de pedidos del día.
  - Ingresos del día (subtotal menos descuentos).
  - Ingresos por envíos del día.
- Alerta sonora cuando se recibe un pedido nuevo.
- Actualización automática cada 15 segundos sin recargar la página.

---

### 2.7 Gestión de Sucursales

El sistema incluye gestión para **2 sucursales**: Sucursal Atasta y Sucursal AV Universidad.

- Alta de sucursales con:
  - Nombre de la sucursal.
  - Dirección completa.
  - Número de teléfono.
  - Número de WhatsApp (con código de país).
  - Horario de atención (días de la semana y horas de apertura/cierre).
  - Coordenadas geográficas (para el mapa).
  - Enlace a Didi Food (opcional).
- Edición de sucursales existentes.
- Eliminación de sucursales (solo si no tienen pedidos asociados).
- Activación o desactivación de sucursales (oculta del catálogo público sin eliminar).
- Control del orden de aparecimiento.
- Generación automática de texto legible del horario.

---

### 2.8 Disponibilidad de Productos por Sucursal

- Vista tipo matriz: cada sucursal como pestaña, con todos los productos listados.
- Para cada producto en cada sucursal se puede configurar:
  - Disponibilidad individual (activo/inactivo solo en esa sucursal).
  - Precio especial por sucursal (sobreescribe el precio general del producto).
  - Opciones disponibles por sucursal (mojado, seco, cochinita, lechón).
- Guardado automático al modificar cada producto.
- Indicadores visuales de estatus (activo/inactivo).
- Visualización por categoría (Comida, Bebida).

---

### 2.9 Gestión de Cupones de Descuento

- Alta de cupones con código y porcentaje de descuento (1% a 100%).
- Activación o desactivación de cupones sin eliminarlos.
- Eliminación de cupones.
- Los códigos se validan automáticamente antes de aplicar el descuento al pedido.
- Los cupones funcionan tanto en pedidos por WhatsApp como por MercadoPago.

---

### 2.10 Perfil del Administrador

- Visualización de los datos del administrador actual.
- Cambio de contraseña (requiere la contraseña actual para confirmar la identidad).
- Cambio de correo electrónico (se valida que no esté registrado por otro usuario).

---

### 2.11 Seguridad del Sistema

- Protección contra ataques en formularios (CSRF).
- Cabeceras de seguridad HTTP configuradas.
- Verificación de precios del lado del servidor (validación contra la base de datos).
- Validación de cupones del lado del servidor.
- Protección de archivos sensibles del sistema.
- Redirección automática a conexiones seguras (HTTPS).
- Compresión de archivos para mejor rendimiento.
- Cache de archivos estáticos para carga más rápida.
- Límite de velocidad en inicio de sesión (máximo 5 intentos por minuto).
- Límite de velocidad en creación de pedidos (máximo 20 por minuto).

---

### 2.12 Infraestructura y Despliegue

- El sistema se despliega en el dominio **lastortasdelchiche.com**.
- Se proporciona configuración de despliegue con Docker.
- Base de datos SQLite incluida.
- Configuración de compresión y caché en el servidor.

---

## FUERA DEL ALCANCE

Las siguientes funcionalidades **NO están incluidas** en este sistema. Cualquier solicitud para incorporarlas será considerada como un desarrollo adicional que requerirá cotización y aprobación por separado:

- Aplicación móvil nativa (Android/iOS).
- Sistema de facturación electrónica (CFDI).
- Sistema de múltiples idiomas.
- Sistema de múltiples empresas (multiempresa).
- Roles y permisos avanzados (actualmente solo existe un usuario administrador).
- Notificaciones por correo electrónico.
- Notificaciones push en tiempo real.
- Sistema de chat interno o con clientes.
- Dashboard con gráficas estadísticas avanzadas.
- Reportes exportables a PDF o Excel.
- Sistema de puntos, fidelización o lealtad para clientes.
- Tracking de repartidor en tiempo real.
- Integración con sistemas de punto de venta (TPV/POS).
- Integración con sistemas contables o ERP.
- Administración de empleados y turnos.
- Control de inventario y recetas.
- Módulo de compras a proveedores.
- Módulo de banquetería o eventos.
- Sistema de reseñas y calificaciones de clientes.
- Blog, noticias o sección de contenido.
- SEO avanzado (más allá del básico incluido).
- Múltiples monedas.
- Sistema de suscripciones o membresías.
- Programa de referidos.
- Integración con Google Analytics o Meta Pixel.
- Integración automática con redes sociales.
- Análisis de comportamiento de usuario.
- Gestión de descuentos por volumen o mayoreo.
- Sistema de códigos de barras o QR.
- Gestión de propinas.
- Sistema de reservaciones o filas virtuales.
- Kiosco de autoservicio.
- Módulo de cocina (display de pedidos).

---

Cualquier funcionalidad no descrita expresamente en este Anexo será considerada un desarrollo adicional y estará sujeta a cotización y aprobación por separado por ambas partes.

---

Este Anexo A forma parte integral del Contrato de Desarrollo, Mantenimiento y Soporte de Software celebrado entre las partes, y describe el alcance funcional completo del sistema a entregar.

---

## FIRMAS

**EL PROVEEDOR:**

_________________________
**JOSE MANRIQUE MONTERO PALMA**

**EL CLIENTE:**

_________________________
**Las Tortas Del Chiche**

**Fecha:** 21 de julio de 2026
