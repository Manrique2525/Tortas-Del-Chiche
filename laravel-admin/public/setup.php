<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
set_time_limit(300);

$baseDir = __DIR__ . '/..';
$log = [];

function logStep($msg, $ok, $detail = '') {
    global $log;
    $log[] = ['msg' => $msg, 'ok' => $ok, 'detail' => $detail];
}

// 1. Crear bootstrap/cache si no existe
$cacheDir = $baseDir . '/bootstrap/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
    logStep('bootstrap/cache', is_dir($cacheDir), is_dir($cacheDir) ? 'Creada' : 'No se pudo crear — créala manualmente por WinSCP');
} else {
    logStep('bootstrap/cache', true, 'Ya existe');
}

// 2. Permisos
foreach (['storage', 'bootstrap', 'bootstrap/cache'] as $dir) {
    $p = $baseDir . '/' . $dir;
    if (is_dir($p)) {
        @chmod($p, 0775);
        $all = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p));
        foreach ($all as $f) { if ($f->isDir()) @chmod($f->getPathname(), 0775); else @chmod($f->getPathname(), 0664); }
        logStep("Permisos $dir", true);
    } else {
        logStep("Permisos $dir", false, 'No existe');
    }
}

// 3. storage:link — copiar directamente si symlink deshabilitado
$publicStorage = $baseDir . '/public/storage';
$storageSource = $baseDir . '/storage/app/public';
if (!file_exists($publicStorage)) {
    if (@symlink($storageSource, $publicStorage)) {
        logStep('storage:link', true, 'Symlink creado');
    } else {
        if (is_dir($storageSource)) {
            $r = copyDir($storageSource, $publicStorage);
            logStep('storage:link', $r, $r ? 'Carpeta copiada (symlink deshabilitado)' : 'No se pudo copiar');
        } else {
            @mkdir($publicStorage, 0775, true);
            logStep('storage:link', true, 'Carpeta public/storage creada (storage/app/public vacía)');
        }
    }
} else {
    logStep('storage:link', true, 'Ya existe');
}

function copyDir($src, $dst) {
    if (!is_dir($dst)) @mkdir($dst, 0775, true);
    $items = @scandir($src);
    if (!$items) return false;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $s = $src . '/' . $item;
        $d = $dst . '/' . $item;
        if (is_dir($s)) { copyDir($s, $d); }
        else { @copy($s, $d); @chmod($d, 0664); }
    }
    return true;
}

// 4. Check vendor/
if (!file_exists($baseDir . '/vendor/autoload.php')) {
    logStep('vendor/', false, 'vendor/ no encontrada en: ' . $baseDir . '/vendor/ — súbela por WinSCP a la raíz del proyecto (NO dentro de public/)');
} else {
    logStep('vendor/', true, 'Encontrada');

    // 5. Bootstrap Laravel
    try {
        require $baseDir . '/vendor/autoload.php';
        $app = require_once $baseDir . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        // 6. APP_KEY
        $envContent = @file_get_contents($baseDir . '/.env');
        $hasKey = (strpos($envContent, 'base64:') !== false);
        if (!$hasKey) {
            $kernel->call('key:generate', ['--force' => true]);
            $output = method_exists($kernel, 'output') ? $kernel->output() : '';
            $envContent = @file_get_contents($baseDir . '/.env');
            $hasKey = (strpos($envContent, 'base64:') !== false);
            logStep('APP_KEY', $hasKey, trim($output));
        } else {
            logStep('APP_KEY', true, 'Ya configurada');
        }

        // 7. Migrate
        $kernel->call('migrate', ['--force' => true]);
        $output = method_exists($kernel, 'output') ? $kernel->output() : '';
        logStep('Migraciones', true, trim($output));

        // 8. Seed
        $kernel->call('db:seed', ['--force' => true]);
        $output = method_exists($kernel, 'output') ? $kernel->output() : '';
        logStep('Seeders', true, trim($output));

        // 9. Cache clear
        foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $cmd) {
            $kernel->call($cmd);
            $output = method_exists($kernel, 'output') ? $kernel->output() : '';
            logStep("artisan $cmd", true, trim($output));
        }

    } catch (Throwable $e) {
        logStep('Bootstrap Laravel', false, $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — Las Tortas Del Chiche</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:sans-serif;background:#1a1a2e;color:#eee;padding:40px 20px;display:flex;justify-content:center}
        .box{max-width:700px;width:100%}
        h1{color:#FF6B35;margin-bottom:20px}
        .row{display:flex;align-items:flex-start;padding:10px 0;border-bottom:1px solid #0f3460}
        .icon{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;margin-right:10px;flex-shrink:0}
        .ok{background:#27ae60;color:#fff}
        .fail{background:#e74c3c;color:#fff}
        .name{font-weight:600}
        .detail{font-size:.8rem;color:#888;white-space:pre-wrap;background:#0d1b2a;padding:6px 8px;border-radius:4px;margin-top:4px;max-height:120px;overflow-y:auto}
        .success{background:#27ae60;color:#fff;padding:16px;border-radius:8px;margin-top:20px;text-align:center}
        .warn{background:#333;color:#ccc;padding:16px;border-radius:8px;margin-top:20px;text-align:center}
        a{color:#FF6B35}
    </style>
</head>
<body>
<div class="box">
    <h1>Las Tortas Del Chiche — Setup</h1>
    <?php foreach ($log as $s): ?>
    <div class="row">
        <div class="icon <?= $s['ok'] ? 'ok' : 'fail' ?>"><?= $s['ok'] ? '✓' : '✗' ?></div>
        <div>
            <div class="name"><?= htmlspecialchars($s['msg']) ?></div>
            <?php if ($s['detail']): ?><div class="detail"><?= htmlspecialchars($s['detail']) ?></div><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="success">Completado. <a href="/">Ir al sitio</a></div>
    <div class="warn">Borra <strong>setup.php</strong> ahora por seguridad.</div>
</div>
</body>
</html>
