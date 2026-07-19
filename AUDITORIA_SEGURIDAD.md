# Auditoría de Seguridad — Las Tortas El Chiche

**Fecha:** 2026-07-19
**Alcance:** Full Stack (Laravel 12, PHP 8.3, MySQL, JS, HTML, CSS)
**Metodología:** OWASP Top 10, OWASP ASVS, Laravel Security Best Practices
**Calificación general: 45/100 — NO APTO PARA PRODUCCIÓN sin correcciones**

---

## Resumen Ejecutivo

Se realizó una auditoría completa del proyecto "Las Tortas El Chiche", que consta de:

- **Frontend estático:** HTML + CSS + JavaScript (`/js/cart.js`, `/js/products.js`, `/js/script.js`)
- **Backend Laravel 12:** API REST, Admin Dashboard, Mercado Pago, gestión de productos/pedidos/sucursales
- **Infraestructura:** Docker, Render, SQLite (desarrollo) / MySQL (producción)

Se identificaron **62 hallazgos** distribuidos en:
- **12 CRÍTICOS** — Requieren acción inmediata
- **18 ALTOS** — Deben corregirse antes de salir a producción
- **20 MEDIOS** — Corregir en el primer sprint
- **12 BAJOS** — Mejores prácticas

---

## 1. HALLAZGOS POR SEVERIDAD

---

### 🔴 CRÍTICOS (12)

#### C1 — Token de producción de Mercado Pago expuesto en git

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/.env.render` |
| Línea | 40 |
| Riesgo | Cualquiera con acceso al repo puede procesar/reembolsar pagos |

**Explicación:** El token `APP_USR-8272766546147597-071900-17312f03eb17cd17387c06dcae241dea-3549381537` (producción real) está hardcodeado en un archivo versionado.

**Solución:**
1. Rotar el token inmediatamente en el dashboard de Mercado Pago
2. Eliminar `.env.render` del repo (`git rm --cached` + `.gitignore`)
3. Configurar como variable de entorno secreta en Render

---

#### C2 — Password admin hardcodeado con fallback débil

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/config/app.php` |
| Línea | 126 |
| Riesgo | Acceso no autorizado al panel admin |

**Explicación:** `env('ADMIN_PASSWORD', 'tortas2026')` — el fallback `tortas2026` es débil, guesseable y público en el código fuente.

**Solución:** Eliminar el fallback, forzar que la variable de entorno siempre esté definida:
```php
'admin_password' => env('ADMIN_PASSWORD'),
```

---

#### C3 — Sin validación de firma en Webhook de Mercado Pago

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Líneas | 186-237 |
| Riesgo | Falsificación de notificaciones de pago |

**Explicación:** El webhook acepta cualquier POST sin verificar el header `X-Signature`. Mercado Pago envía una firma HMAC que debe validarse.

**Solución:**
```php
use MercadoPago\Notification;

$notification = new Notification();
if (!$notification->verify($request->header('X-Signature'), $request->getContent())) {
    Log::warning('Invalid webhook signature');
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

---

#### C4 — Sin validación de monto en Webhook

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Líneas | 198-231 |
| Riesgo | Pagar $1 por una orden de $300 |

**Explicación:** El webhook marca la orden como `pagado` sin verificar que `payment.transaction_amount` coincida con `order.total`.

**Solución:**
```php
$payment = $client->get((int) $paymentId);
$order = Order::findOrFail($orderId);

if ((float) $payment->transaction_amount !== (float) $order->total) {
    Log::warning('Monto不一致', ['order' => $orderId, 'expected' => $order->total, 'received' => $payment->transaction_amount]);
    return response()->json(['received' => true]);
}
if ($payment->currency_id !== 'MXN') {
    Log::warning('Moneda不一致');
    return response()->json(['received' => true]);
}
```

---

#### C5 — Precios client-side sin recalcular en servidor (OrderController)

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/OrderController.php` |
| Líneas | 17-57 |
| Riesgo | Robo total: pagar $0.01 por productos de $200 |

**Explicación:** `subtotal`, `delivery_fee`, `discount`, `total` y `unit_price` vienen del cliente sin verificación contra la base de datos.

**Solución:**
```php
function recalculateOrderTotal(array $items, float $deliveryFee, ?Coupon $coupon): array {
    $verifiedTotal = 0;
    foreach ($items as $item) {
        $product = Product::find($item['product_id']);
        if (!$product || !$product->active) {
            throw new ValidationException('Producto no disponible: ' . $item['product_name']);
        }
        if ((float) $item['unit_price'] !== (float) $product->price) {
            throw new ValidationException('Precio inconsistente: ' . $product->name);
        }
        $verifiedTotal += $product->price * (int) $item['quantity'];
    }
    $verifiedTotal += $deliveryFee;
    if ($coupon) {
        $verifiedTotal -= $verifiedTotal * ($coupon->discount_percent / 100);
    }
    return ['total' => max(0, $verifiedTotal)];
}
```

---

#### C6 — Precios client-side sin recalcular en createPreference

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Líneas | 65-93 |
| Riesgo | Pagar monto manipulado vía MP |

**Explicación:** Misma vulnerabilidad que C5 pero en la creación de la preferencia de pago.

**Solución:** Recalcular totales desde productos en DB antes de crear la preferencia.

---

#### C7 — Sin regeneración de sesión en login (Session Fixation)

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Admin/AuthController.php` |
| Línea | 26 |
| Riesgo | Atacante puede fijar session ID y secuestrar sesión admin |

**Solución:**
```php
session()->regenerate(true); // Destruye sesión anterior
session(['admin_authenticated' => true]);
```

---

#### C8 — Password único compartido — sin roles

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Admin/AuthController.php` |
| Líneas | 21-27 |
| Riesgo | Sin auditoría, sin revocación individual, sin MFA |

**Solución:** Implementar autenticación con Laravel Breeze/Jetstream o al menos múltiples usuarios con `Hash::check()`.

---

#### C9 — Sin rate limiting en login admin

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/routes/web.php` |
| Línea | 19 |
| Riesgo | Fuerza bruta ilimitada contra el password admin |

**Solución:**
```php
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.post');
```

---

#### C10 — APP_KEY vacía

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/.env.render` |
| Línea | 3 |
| Riesgo | Encriptación de sesión/cookies inválida |

**Explicación:** `APP_KEY=` está vacío. Se genera en cada build con `|| true` que traga errores.

**Solución:** Generar key fija (`php artisan key:generate --show`) y setearla como env var en Render. Quitar `|| true` del Dockerfile.

---

#### C11 — Sin validación de cupones server-side

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/OrderController.php` |
| Líneas | 27, 55 |
| Riesgo | Descuentos fraudulentos |

**Explicación:** El `coupon_code` se almacena pero nunca se valida contra la tabla `coupons`.

**Solución:**
```php
$coupon = null;
if (!empty($validated['coupon_code'])) {
    $coupon = Coupon::where('code', strtoupper(trim($validated['coupon_code'])))
        ->where('active', true)
        ->first();
    if (!$coupon) {
        throw new ValidationException('Cupón inválido');
    }
}
```

---

#### C12 — Stored XSS vía productos

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/js/products.js` |
| Líneas | 54-62 |
| Riesgo | Ejecución de JS arbitrario en navegadores de clientes |

**Explicación:** `product.name` y `product.description` se insertan vía `${}` en `innerHTML`. Un admin puede crear productos con `<img src=x onerror=alert('XSS')>`.

**Solución:** Usar `textContent` en lugar de `innerHTML` para texto plano, o sanitizar con DOMPurify:
```javascript
// En lugar de innerHTML con interpolación
const nameSpan = document.createElement('span');
nameSpan.textContent = product.name;
card.appendChild(nameSpan);
```

---

### 🟠 ALTOS (18)

#### H1 — Encriptación de sesión deshabilitada

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/config/session.php` |
| Línea | 50 |
| Solución | `SESSION_ENCRYPT=true` en `.env.render` |

---

#### H2 — Trust all proxies `0.0.0.0/0`

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/bootstrap/app.php` |
| Línea | 18 |
| Solución | Limitar a IPs del load balancer de Render |

---

#### H3 — Sin security headers

| Campo | Valor |
|---|---|
| Archivo | — |
| Solución | Agregar middleware que inyecte: `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Content-Security-Policy`, `Referrer-Policy` |

---

#### H4 — Sin rate limiting en APIs públicas

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/routes/api.php` |
| Líneas | 9-33 |
| Solución | Agrupar rutas API con `->middleware('throttle:30,1')` |

---

#### H5 — Enumeración de cupones vía API

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/routes/api.php` |
| Líneas | 24-30 |
| Solución | No exponer códigos de cupón en la respuesta pública, solo si están activos y requerir referencia del producto |

---

#### H6 — AdminAuth sin validación de IP/User-Agent

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Middleware/AdminAuth.php` |
| Línea | 12 |
| Solución | Agregar validación de IP y User-Agent contra los almacenados en sesión |

---

#### H7 — Sin límite absoluto de sesión

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Middleware/AdminAuth.php` |
| Líneas | 10-17 |
| Solución | Almacenar `last_activity` y forzar re-login después de X horas |

---

#### H8 — Datos bancarios hardcodeados en JS

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/js/cart.js` |
| Líneas | 15-17 |
| Solución | Servir datos bancarios desde un endpoint `/api/bank-info` con caché |

---

#### H9 — Sin idempotencia en webhook

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Solución | Verificar `if ($order->mp_payment_id === (string)$paymentId && $order->status === 'pagado') return;` |

---

#### H10 — Sin verificación de stock

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/OrderController.php` |
| Solución | Validar existencia y disponibilidad de cada `product_id` |

---

#### H11 — Stack traces expuestos en errores

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Líneas | 75, 110-111 |
| Solución | No incluir `$e->getTraceAsString()` en respuestas JSON |

---

#### H12 — SQL LIKE sin escape de wildcards

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Admin/OrderController.php` |
| Líneas | 26-29 |
| Solución | Escapar `%` y `_` en el search term: `str_replace(['%', '_'], ['\\%', '\\_'], $search)` |

---

#### H13 — `|| true` en Dockerfile silencia errores

| Campo | Valor |
|---|---|
| Archivo | `Dockerfile` |
| Línea | 20 |
| Solución | Quitar `|| true`, el build debe fallar si key:generate falla |

---

#### H14 — Sin FK en `orders.branch`

| Campo | Valor |
|---|---|
| Archivo | Migración `2026_07_18_000001_create_orders_table.php` |
| Solución | Agregar columna `branch_id` FK a `sucursales.id`, migrar datos existentes |

---

#### H15 — Sin índice en `orders.status`

| Campo | Valor |
|---|---|
| Archivo | Migración `2026_07_18_000001_create_orders_table.php` |
| Solución | `$table->index('status')` |

---

#### H16 — Sin índice en `order_items.order_id`

| Campo | Valor |
|---|---|
| Archivo | Migración `2026_07_18_000002_create_order_items_table.php` |
| Solución | `$table->foreignId('order_id')->constrained()->cascadeOnDelete()->index()` |

---

#### H17 — Archivo .env accesible

| Campo | Valor |
|---|---|
| Archivo | `.htaccess` (ambos niveles) |
| Solución | Agregar:
```
<FilesMatch "\.env|composer\.json|composer\.lock|database\.sqlite">
    Require all denied
</FilesMatch>
```

---

#### H18 — URLs de producción hardcodeadas

| Campo | Valor |
|---|---|
| Archivo | `laravel-admin/app/Http/Controllers/Api/MercadoPagoController.php` |
| Líneas | 144-146 |
| Solución | Usar `config('app.url')` o variable de entorno |

---

### 🟡 MEDIOS (20)

| # | Hallazgo | Archivo | Solución |
|---|---|---|---|
| M1 | Sin CORS configurado | — | Crear `config/cors.php` con orígenes explícitos |
| M2 | Session lifetime 120min | `.env.render` | Reducir a 60min |
| M3 | Mail driver en `log` | `.env.render` | Configurar SMTP/SES/Postmark |
| M4 | SQLite potencialmente expuesto | `database.sqlite` | Mover fuera del webroot |
| M5 | Order ID enumeration | `MercadoPagoController.php:239` | Agregar auth o HMAC |
| M6 | Sin verificación server-side post-MP | `js/cart.js:1774` | Polling a endpoint autenticado |
| M7 | Cupones sin límites | `Coupon.php` | Agregar `max_uses`, `expires_at`, `used_count` |
| M8 | Transiciones de estado no validadas | `OrderController.php:65-67` | State machine con array de transiciones válidas |
| M9 | Productos inactivos expuestos | `ProductController.php:13` | Filtrar `where('active', true)` |
| M10 | Race condition en generateUniqueKey | `Branch.php` | Usar `DB::transaction` + `firstOrCreate` |
| M11 | isOpenAttribute sin soporte midnight-crossing | `Branch.php` | Implementar lógica de rangos >24h |
| M12 | Sin Soft Deletes en Orders | — | Agregar `SoftDeletes` trait |
| M13 | 4 booleanos en vez de pivot | Migración `000008` | Crear `meat_types` + `product_meat_type` pivot |
| M14 | Raw SQL en migración | Migración `000004` | Usar `Schema::table()` |
| M15 | Sin validación JSON en schedule | `BranchController.php:36-40` | Validar estructura antes de guardar |
| M16 | XSS en dashboard: producto en JS | `dashboard.blade.php:358` | Usar `@json($product->name)` |
| M17 | XSS en toast con innerHTML | `js/cart.js:1862` | Usar `textContent` |
| M18 | XSS en items del carrito | `js/cart.js:718` | Usar `textContent` |
| M19 | Datos de orden expuestos | `routes/api.php:17-23` | Limitar a admin autenticado |
| M20 | Dead code: User model | `User.php` | Limpiar o implementar auth |

---

### 🔵 BAJOS (12)

| # | Hallazgo | Solución |
|---|---|---|
| L1 | Fallback `prod` en `MERCADO_PAGO_ENV` | Cambiar default a `'test'` |
| L2 | `description` VARCHAR(255) | Cambiar a TEXT |
| L3 | `customer_phone` VARCHAR(20) | Cambiar a VARCHAR(30) |
| L4 | Sin CHECK constraint en `status` | Agregar `->check('status IN (...))'` |
| L5 | Endpoint test de MP expuesto | Eliminar `test()` controller |
| L6 | SPA fallback redirige a login | Devolver 404 |
| L7 | Sin password complexity | Agregar `min:8` en login |
| L8 | `binary_mode` puede rechazar pagos | Evaluar si deshabilitar |
| L9 | Sin índices en `products.category`, `products.active` | Agregar índices |
| L10 | Sin índices en `coupons.active`, `sucursales.is_active` | Agregar índices |
| L11 | `password_reset_tokens` sin FK | Agregar FK constraint |
| L12 | Versionado permisivo | Bloquear composer.lock |

---

## 2. TABLA RESUMEN

| Categoría | 🔴 CRÍTICO | 🟠 ALTO | 🟡 MEDIO | 🔵 BAJO |
|---|---|---|---|---|
| Mercado Pago | 4 | 3 | 2 | 1 |
| Autenticación / Admin | 4 | 2 | — | 1 |
| API / Endpoints | 1 | 3 | 3 | 1 |
| Base de Datos | — | 3 | 5 | 4 |
| Frontend / JS | 1 | 1 | 4 | — |
| Configuración / Env | 2 | 4 | 3 | 1 |
| Dependencias | — | — | — | 1 |
| Docker / Deploy | — | 1 | — | — |
| Código / Arquitectura | — | 1 | 3 | 3 |
| **TOTAL** | **12** | **18** | **20** | **12** |

---

## 3. PLAN DE ACCIÓN POR PRIORIDAD

### 🔥 DÍA 1 — CRÍTICO (No salir a producción sin esto)

| Orden | Acción | Esfuerzo |
|---|---|---|
| 1 | **Rotar token MP** en dashboard de Mercado Pago | 5 min |
| 2 | **Eliminar `.env.render` del repo**, agregar a `.gitignore` | 5 min |
| 3 | **Configurar APP_KEY fija** en variables de entorno de Render | 5 min |
| 4 | **Cambiar password admin** a uno fuerte, remover fallback `tortas2026` | 10 min |
| 5 | **Agregar validación de firma X-Signature** en webhook MP | 2-3 hrs |
| 6 | **Agregar validación de monto** en webhook (`transaction_amount` vs `order.total`) | 30 min |
| 7 | **Recalcular precios server-side** en `OrderController::store` y `createPreference` | 4-6 hrs |
| 8 | **Agregar rate limiting** (`throttle`) a `/admin/login` y `/api/*` | 30 min |
| 9 | **Agregar `session()->regenerate()`** después de login | 5 min |
| 10 | **Validar cupones server-side** (existencia, vigencia, recalcular descuento) | 2 hrs |

### ⚡ SEMANA 1 — ALTA PRIORIDAD

| Orden | Acción | Esfuerzo |
|---|---|---|
| 11 | Agregar `SESSION_ENCRYPT=true` | 5 min |
| 12 | Acotar `trustProxies` a IPs de Render | 15 min |
| 13 | Agregar security headers (HSTS, XFO, CSP, X-Content-Type-Options) | 1 hr |
| 14 | Proteger `.env`, `composer.json`, `database.sqlite` en `.htaccess` | 15 min |
| 15 | Agregar idempotencia en webhook (prevenir pagos duplicados) | 30 min |
| 16 | Agregar índices en `orders.status`, `orders.branch`, `order_items.order_id` | 15 min |
| 17 | Mover datos bancarios a API (servir desde backend) | 1 hr |
| 18 | Implementar autenticación de usuarios real (no password compartido) | 4-8 hrs |
| 19 | Sanitizar `innerHTML` en `products.js` y `cart.js` (textContent + DOMPurify) | 2 hrs |
| 20 | Eliminar endpoint `/api/mercadopago/test` en producción | 5 min |

### 📋 SPRINT 1 — PRIORIDAD MEDIA

| Orden | Acción |
|---|---|
| 21 | Configurar CORS explícitamente |
| 22 | Reducir `SESSION_LIFETIME` a 60 min |
| 23 | Configurar mail driver real (SMTP/SES/Postmark) |
| 24 | Usar UUIDs para `external_reference` en MP |
| 25 | Agregar límites de uso y expiración a cupones |
| 26 | Implementar state machine para transiciones de estado en órdenes |
| 27 | Agregar rate limiting por IP en uploads de comprobantes |
| 28 | Agregar `SoftDeletes` en modelo `Order` |
| 29 | Agregar logging de IP y User-Agent al crear órdenes |
| 30 | Normalizar opciones de carne (tabla pivot en vez de 4 booleanos) |

### 🔧 MEJORAS CONTINUAS

| Orden | Acción |
|---|---|
| 31 | Cambiar fallback `MERCADO_PAGO_ENV` a `'test'` |
| 32 | Cambiar `description` de VARCHAR(255) a TEXT |
| 33 | Cambiar `customer_phone` de VARCHAR(20) a VARCHAR(30) |
| 34 | Agregar CHECK constraint en columna `status` |
| 35 | Eliminar endpoint `/api/mercadopago/test` |
| 36 | Agregar índices en `products.category`, `products.active`, `coupons.active`, `sucursales.is_active` |
| 37 | Mejorar lógica de horarios que cruzan medianoche en `Branch::isOpen` |
| 38 | Corregir race condition en `Branch::generateUniqueKey` |

---

## 4. CHECKLIST PRE-PRODUCCIÓN

Antes de salir a producción, **TODO** esto debe estar verificado:

### Seguridad
- [ ] Token MP rotado y almacenado solo como env var en Render
- [ ] `.env.render` eliminado del repositorio
- [ ] Firma `X-Signature` validada en webhook
- [ ] Monto validado contra la orden en el webhook
- [ ] Precios recalculados server-side (nunca confiar en el cliente)
- [ ] Cupones validados y descuentos recalculados server-side
- [ ] Rate limiting en login y APIs públicas
- [ ] Sesión regenerada después de login (`session()->regenerate()`)
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=strict`
- [ ] `APP_KEY` generada, fija y no vacía
- [ ] `APP_DEBUG=false`
- [ ] `trustProxies` acotado a IPs del load balancer
- [ ] CORS configurado con orígenes explícitos
- [ ] Security headers implementados (HSTS, XFO, CSP, X-Content-Type-Options, Referrer-Policy)
- [ ] `.htaccess` protege `.env`, `storage/`, `database.sqlite`, `composer.json`, `composer.lock`
- [ ] Datos bancarios servidos desde backend, no hardcodeados en JS
- [ ] `innerHTML` sanitizados en todos los archivos JS
- [ ] Roles de admin implementados (no password compartido)
- [ ] Passwords almacenados con hash (bcrypt)

### Base de Datos
- [ ] Índices en `orders.status`, `orders.branch`, `order_items.order_id`
- [ ] FK `orders.branch` → `sucursales.key` (o `branch_id`)
- [ ] Soft deletes en `orders`

### Mercado Pago
- [ ] Idempotencia en webhook (prevenir procesamiento duplicado)
- [ ] Verificación de `mp_payment_id` duplicado
- [ ] Estado de orden verificado server-side (no confiar en parámetros URL)
- [ ] `back_urls` configuradas por ambiente

### Infraestructura
- [ ] HTTPS forzado (redirección HTTP → HTTPS)
- [ ] Mail driver configurado (no `log`)
- [ ] Logging configurado con rotación
- [ ] Backup automatizado de base de datos
- [ ] Monitorización de errores (Sentry, Bugsnag, etc.)
- [ ] Health check endpoint
- [ ] Docker build sin `|| true` (fallar en errores)

---

## 5. CALIFICACIONES FINALES

| Dimensión | Puntaje | Comentario |
|---|---|---|
| **Seguridad** | 35/100 | Token expuesto, auth débil, sin validación server-side de precios |
| **Arquitectura** | 60/100 | Bien organizado pero con problemas de normalización y FKs |
| **Escalabilidad** | 50/100 | Sin índices críticos, SQLite en lugar de MySQL para producción |
| **Rendimiento** | 55/100 | N+1 mitigado pero faltan índices en consultas frecuentes |
| **Calidad de Código** | 65/100 | Código limpio en general, pero con malas prácticas de seguridad |
| **Mantenibilidad** | 70/100 | Buena organización, nombres claros, migraciones ordenadas |
| **General** | **45/100** | **NO AUTORIZADO para producción** |

---

*Documento generado automáticamente como parte de la auditoría de seguridad pre-producción.*
*Fecha: 2026-07-19*
