<?php
/**
 * Manual autoloader for Dompdf v2 (no Composer)
 * Include this file wherever Dompdf is needed.
 */

// Include Dompdf's own autoloader (this file exists in v2+)
$dompdfAutoload = __DIR__ . '/dompdf/src/Autoloader.php';
if (file_exists($dompdfAutoload)) {
    require_once $dompdfAutoload;
    \Dompdf\Autoloader::register();
    return;
}

// Fallback: manual PSR-4 autoload for Dompdf v2
spl_autoload_register(function ($class) {
    $prefixes = [
        'Dompdf\\'          => __DIR__ . '/dompdf/src/',
        'FontLib\\'         => __DIR__ . '/dompdf/lib/php-font-lib/src/FontLib/',
        'Svg\\'             => __DIR__ . '/dompdf/lib/php-svg-lib/src/Svg/',
        'Sabberworm\\CSS\\' => __DIR__ . '/dompdf/lib/php-svg-lib/src/Sabberworm/CSS/',
        'Masterminds\\'     => __DIR__ . '/dompdf/lib/html5lib/src/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});