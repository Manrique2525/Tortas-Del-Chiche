<?php
/**
 * INSTALADOR - Las Tortas Del Chiche
 *
 * 1. Sube este archivo y tortas-del-chiche.zip a public_html/ via WinSCP
 * 2. Visita https://lastortasdelchiche.com/setup.php
 * 3. ELIMINA este archivo y el ZIP despues
 */

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Instalador</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px;max-width:800px;margin:0 auto}";
echo "h1{color:#e63946} .ok{color:#2ecc71} .err{color:#e74c3c} pre{background:#0a0a1a;padding:15px;border-radius:8px;overflow-x:auto}</style></head><body>";
echo "<h1>Instalador - Las Tortas Del Chiche</h1>";
echo "<pre>";
flush();

$zipFile = __DIR__ . '/tortas-del-chiche.zip';
$targetDir = __DIR__;

if (!file_exists($zipFile)) {
    echo "<span class='err'>ERROR: No se encontro tortas-del-chiche.zip</span>\n";
    echo "Ruta: $zipFile\n";
    echo "Sube el ZIP a public_html/ (mismo lugar que este archivo).\n";
    exit(1);
}

if (!class_exists('ZipArchive')) {
    echo "<span class='err'>ERROR: ZipExtension no habilitada.</span>\n";
    exit(1);
}

echo "=== PASO 1: Abriendo ZIP ===\n";
$zip = new ZipArchive();
if ($zip->open($zipFile) !== TRUE) {
    echo "<span class='err'>ERROR: No se pudo abrir el ZIP.</span>\n";
    exit(1);
}

$totalFiles = $zip->numFiles;
echo "ZIP: <span class='ok'>$totalFiles</span> archivos\n";
echo "Destino: $targetDir\n\n";
flush();

echo "=== PASO 2: Guardando .env ===\n";
$envPath = $targetDir . '/laravel-admin/.env';
$envBackup = null;
if (is_file($envPath)) {
    $envBackup = file_get_contents($envPath);
    echo "  .env guardado\n";
} else {
    echo "  No hay .env previo (lo creas despues)\n";
}
flush();

echo "\n=== PASO 3: Extrayendo ===\n";
flush();

$extracted = 0;
$errors = 0;
for ($i = 0; $i < $totalFiles; $i++) {
    $name = str_replace('\\', '/', $zip->getNameIndex($i));

    if (basename($name) === '.env') {
        continue;
    }

    $fullPath = $targetDir . '/' . $name;
    $dir = dirname($fullPath);

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (substr($name, -1) !== '/') {
        $content = $zip->getFromIndex($i);
        if ($content !== false) {
            @file_put_contents($fullPath, $content);
        } else {
            $errors++;
        }
    }

    $extracted++;
    if ($extracted % 1000 == 0) {
        echo "  $extracted / $totalFiles...\n";
        flush();
    }
}
$zip->close();
echo "  Extraidos <span class='ok'>$extracted</span> archivos";
if ($errors > 0) echo " (<span class='err'>$errors errores</span>)";
echo "\n\n";

if ($envBackup !== null) {
    @file_put_contents($envPath, $envBackup);
    echo "  .env restaurado\n\n";
}

echo "=== PASO 4: Verificando ===\n";
$checks = [
    'laravel-admin/vendor/autoload.php' => 'Vendor',
    'laravel-admin/vendor/stripe/stripe-php' => 'Stripe',
    'laravel-admin/public/index.php' => 'Laravel index.php',
    'laravel-admin/public/.htaccess' => '.htaccess',
    'laravel-admin/artisan' => 'Artisan',
];

$allOk = true;
foreach ($checks as $path => $label) {
    $exists = is_file($targetDir . '/' . $path) || is_dir($targetDir . '/' . $path);
    $status = $exists ? "<span class='ok'>OK</span>" : "<span class='err'>FALTA</span>";
    if (!$exists) $allOk = false;
    echo "  $label: $status\n";
}

// Crear storage dirs
$dirs = ['laravel-admin/storage/app/public','laravel-admin/storage/framework/cache/data','laravel-admin/storage/framework/sessions','laravel-admin/storage/framework/views','laravel-admin/storage/logs'];
foreach ($dirs as $d) { if (!is_dir($targetDir.'/'.$d)) @mkdir($targetDir.'/'.$d, 0755, true); }

// Storage link
$link = $targetDir.'/laravel-admin/public/storage';
$target = $targetDir.'/laravel-admin/storage/app/public';
if (!file_exists($link)) @symlink($target, $link);

echo "\n========================================\n";
if ($allOk) {
    echo "<span class='ok'>INSTALACION COMPLETADA</span>\n";
} else {
    echo "<span class='err'>INSTALACION CON ADVERTENCIAS</span>\n";
}
echo "========================================\n\n";
echo "SIGUIENTE PASO:\n";
echo "1. Crear public_html/laravel-admin/.env (ver abajo)\n";
echo "2. Cambiar document root a public_html/laravel-admin/public\n";
echo "3. Borrar setup.php y tortas-del-chiche.zip\n\n";
echo "CONTENIDO DEL .env:\n";
echo "---\n";
echo 'APP_NAME="Las Tortas Del Chiche"' . "\n";
echo 'APP_ENV=production' . "\n";
echo 'APP_KEY=base64:1XxGK8XiLzAFlnyQXqy50clAaFdKLuiQ+zI5ws0lBhA=' . "\n";
echo 'APP_DEBUG=false' . "\n";
echo 'APP_URL=https://lastortasdelchiche.com' . "\n";
echo 'APP_LOCALE=es' . "\n";
echo 'APP_FALLBACK_LOCALE=es' . "\n";
echo 'APP_MAINTENANCE_DRIVER=file' . "\n";
echo 'BCRYPT_ROUNDS=12' . "\n";
echo 'LOG_CHANNEL=stack' . "\n";
echo 'LOG_STACK=single' . "\n";
echo 'LOG_LEVEL=debug' . "\n";
echo 'DB_CONNECTION=mysql' . "\n";
echo 'DB_HOST=db5020977848.hosting-data.io' . "\n";
echo 'DB_PORT=3306' . "\n";
echo 'DB_DATABASE=dbs15923035' . "\n";
echo 'DB_USERNAME=dbu526052' . "\n";
echo 'DB_PASSWORD=ManriBere281125' . "\n";
echo 'SESSION_DRIVER=database' . "\n";
echo 'SESSION_LIFETIME=120' . "\n";
echo 'SESSION_ENCRYPT=false' . "\n";
echo 'SESSION_PATH=/' . "\n";
echo 'SESSION_DOMAIN=lastortasdelchiche.com' . "\n";
echo 'BROADCAST_CONNECTION=log' . "\n";
echo 'FILESYSTEM_DISK=local' . "\n";
echo 'QUEUE_CONNECTION=database' . "\n";
echo 'CACHE_STORE=database' . "\n";
echo 'MAIL_MAILER=log' . "\n";
echo 'MAIL_FROM_ADDRESS="hello@example.com"' . "\n";
echo 'MAIL_FROM_NAME="${APP_NAME}"' . "\n";
echo 'STRIPE_KEY=pk_test_51QV0wrIVBYLhaibKU8CbPvLsdQn2I5dMChn4A1o9gPd8EqicZ1ayhVEF3pHLn3zI5PzWWT5sMxWHjMM6qcIpno7u00WQCmIgh3' . "\n";
echo 'STRIPE_SECRET=sk_test_51QV0wrIVBYLhaibKXyFaREt0ag0dJcwS0w4BUPuhh4FBQ70vpzb7iu8dpQeD1Z1wQf62W9fb9izFBjkeIkebk0Yi00XcZt6oae' . "\n";
echo 'STRIPE_WEBHOOK_SECRET=' . "\n";
echo 'ADMIN_PASSWORD_HASH=$2y$10$TYTy/hYxxc2.GK1KAmDLou6K5EqgOPnbHQpwBQjUW2ldNA5scFf7O' . "\n";
echo "---\n";
echo "</pre></body></html>";
