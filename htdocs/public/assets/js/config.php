<?php
/**
 * Archivo de configuración central para el frontend
 * 
 * Este archivo es PHP, pero genera contenido JavaScript.
 * Se carga en el <head> de la página.
 */

header('Content-Type: application/javascript');

// Cargar dependencias
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 🔥 LISTA DE VARIABLES REQUERIDAS (SIN EXCEPCIONES)
// ============================================
$requiredVars = [
    'API_KEY',
    'APP_URL',
    'APP_ENV',
    'APP_DEBUG',
    'SESSION_LIFETIME'
];

$missingVars = [];

foreach ($requiredVars as $var) {
    if (!isset($_ENV[$var]) || $_ENV[$var] === '') {
        $missingVars[] = $var;
    }
}

// ============================================
// 🚨 SI FALTA ALGUNA, MOSTRAR ERROR Y DETENER
// ============================================
if (!empty($missingVars)) {
    $errorMsg = "❌ Error de configuración: Faltan variables obligatorias en .env: " . implode(', ', $missingVars);
    error_log($errorMsg);
    
    // En desarrollo, mostrar error en pantalla
    if (($_ENV['APP_ENV'] ?? '') === 'development') {
        die("console.error('" . addslashes($errorMsg) . "');");
    } else {
        // En producción, solo loguear
        die("console.error('Error de configuración del servidor. Revisa los logs.');");
    }
}

// ============================================
// ✅ GENERAR EL CÓDIGO JAVASCRIPT (SIN VALORES POR DEFECTO)
// ============================================
?>
// ============================================
// CONFIGURACIÓN GLOBAL DE LA APLICACIÓN
// ============================================
window.APP_CONFIG = {
    // ============================================
    // API
    // ============================================
    API_KEY: '<?= $_ENV['API_KEY'] ?>',
    API_BASE: '<?= $_ENV['API_BASE'] ?>',
    API_BASE_URL: '<?= $_ENV['APP_URL'] ?>',

    // ============================================
    // ✅ BASE URL PARA ASSETS Y RUTAS
    // ============================================
    BASE_URL: '<?= $_ENV['BASE_URL'] ?? '' ?>',  // ← PUBLIC O VACÍO
    ASSETS_URL: '<?= rtrim($_ENV['APP_URL'] ?? '', '/') . ($_ENV['BASE_URL'] ?? '') ?>/assets',

    // ============================================
    // ENTORNO (SIN VALORES POR DEFECTO)
    // ============================================
    APP_ENV: '<?= $_ENV['APP_ENV'] ?>',
    APP_DEBUG: <?= $_ENV['APP_DEBUG'] === 'true' ? 'true' : 'false' ?>,

    // ============================================
    // SEGURIDAD
    // ============================================
    CSRF_TOKEN: '<?= $_SESSION['csrf_token'] ?? '' ?>',

    // ============================================
    // OTRAS CONFIGURACIONES
    // ============================================
    SESSION_LIFETIME: <?= (int)$_ENV['SESSION_LIFETIME'] ?>,
};
