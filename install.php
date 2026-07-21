<?php
/**
 * INSTALADOR - Las Tortas Del Chiche
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a public_html/ via FTP
 * 2. Visita https://lastortasdelchiche.com/install.php
 * 3. ELIMINA este archivo inmediatamente despues
 * 
 * ⚠️ POR SEGURIDAD: ELIMINA ESTE ARCHIVO DESPUES DE USARLO
 */

echo "<h1>Las Tortas Del Chiche - Instalador</h1>";
echo "<pre>";

// Cambiar al directorio de Laravel
$laravelPath = __DIR__ . '/laravel-admin';

if (!file_exists($laravelPath)) {
    echo "ERROR: No se encontro la carpeta laravel-admin/\n";
    echo "Asegurate de que este archivo este en public_html/ y laravel-admin/ este en el mismo nivel.\n";
    exit(1);
}

chdir($laravelPath);

// Cargar Laravel
require $laravelPath . '/vendor/autoload.php';
$app = require_once $laravelPath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "=== PASO 1: Generando APP_KEY ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'key:generate', '--force']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 2: Ejecutando migraciones ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'migrate', '--force']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 3: Ejecutando seeders ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'db:seed', '--force']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 4: Cacheando configuracion ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'config:cache']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 5: Cacheando rutas ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'route:cache']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 6: Cacheando vistas ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'view:cache']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "=== PASO 7: Creando storage link ===\n";
$kernel->handle(
    new Symfony\Component\Console\Input\ArgvInput(['artisan', 'storage:link', '--force']),
    new Symfony\Component\Console\Output\ConsoleOutput()
);
echo "OK\n\n";

echo "==========================================\n";
echo "INSTALACION COMPLETADA\n";
echo "==========================================\n\n";
echo "SIGUIENTE PASO: ELIMINA ESTE ARCHIVO AHORA\n";
echo "Visita https://lastortasdelchiche.com/laravel-admin/public/ para acceder al admin\n";
echo "Login: admin@lastortasdelchiche.com / torta2026\n";
echo "</pre>";
