<?php
/**
 * Controlador frontal de la aplicación
 * 
 * Todas las peticiones HTTP pasan por este archivo.
 * Se encarga de:
 * 1. Cargar el autoload de Composer
 * 2. Cargar las variables de entorno (.env)
 * 3. Inicializar el enrutador
 * 4. Ejecutar el controlador correspondiente
 */
declare(strict_types=1);

// ============================================
// 1. CONFIGURACIÓN DE ERRORES (SOLO DESARROLLO)
// ============================================

// Mostrar todos los errores en desarrollo (ocultar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ============================================
// 2. CARGAR EL AUTOLOAD DE COMPOSER
// ============================================

require_once __DIR__ . '/../vendor/autoload.php';

// ============================================
// 3. CARGAR VARIABLES DE ENTORNO (.env)
// ============================================

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verificar que el entorno está configurado
if (!isset($_ENV['APP_ENV'])) {
    die('❌ Error: No se encontró el archivo .env. Copia .env.example a .env y configura las variables.');
}

// ============================================
// 4. CONFIGURAR EL ENTORNO (DESARROLLO / PRODUCCIÓN)
// ============================================

$isDevelopment = $_ENV['APP_ENV'] === 'development';

if (!$isDevelopment) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// ============================================
// 5. INICIAR SESIÓN
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', $_ENV['SESSION_HTTP_ONLY'] ?? 1);
    ini_set('session.cookie_secure', $_ENV['SESSION_SECURE'] ?? 0);
    ini_set('session.cookie_samesite', $_ENV['SESSION_SAME_SITE'] ?? 'Lax');
    ini_set('session.gc_maxlifetime', $_ENV['SESSION_LIFETIME'] ?? 3600);
    
    session_start();
}

// ============================================
// 6. GENERAR TOKEN CSRF (si no existe)
// ============================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================
// 7. CARGAR EL ENRUTADOR
// ============================================

// Cargar las rutas definidas en config/routes.php
$routes = require_once __DIR__ . '/../config/routes.php';

// Obtener la URL solicitada (sin el dominio ni los parámetros GET)
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// ============================================
// 🔥 ELIMINAR EL PREFIJO 'public/' SI EXISTE
// ============================================
$prefix = '/public/';
if (strpos($requestPath, $prefix) === 0) {
    $requestPath = substr($requestPath, strlen($prefix) - 1); // Mantener la barra inicial
}

// Eliminar la carpeta base si la aplicación está en una subcarpeta
$basePath = '/';
if (strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath));
}

// Eliminar slashes al inicio y final para normalizar
$requestPath = trim($requestPath, '/');
//echo "Carpeta: ".$requestPath."<br>";
// Si la ruta está vacía, usar '/'
if ($requestPath === '') {
    $requestPath = '/';
}

// ============================================
// 8. BUSCAR LA RUTA EN EL ARCHIVO DE RUTAS
// ============================================

$routeFound = false;
$controller = null;
$method = null;
$middleware = null;
$params = [];

foreach ($routes as $route => $handler) {
    // Convertir la ruta a expresión regular
    $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $route);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $requestPath, $matches)) {
        $routeFound = true;
        $controller = $handler[0];
        $method = $handler[1] ?? 'index';
        $middleware = $handler[2] ?? '';
        
        // Extraer parámetros de la URL (los valores de {id}, etc.)
        if (count($matches) > 1) {
            array_shift($matches);
            $params = $matches;
        }
        break;
    }
}

// ============================================
// ✅ LOG DE DEPURACIÓN (AHORA DESPUÉS DE DEFINIR LAS VARIABLES)
// ============================================
error_log("🔍 Ruta solicitada: " . ($requestPath ?? 'NULL'));
error_log("🔍 Ruta encontrada: " . ($routeFound ? ($route ?? 'SI') : 'NO ENCONTRADA'));
error_log("🔍 Controlador: " . ($controller ?? 'NULL'));
error_log("🔍 Método: " . ($method ?? 'NULL'));

// ============================================
// 9. SI NO SE ENCUENTRA LA RUTA, DEVOLVER 404
// ============================================

if (!$routeFound) {
    http_response_code(404);
    echo "<h1>404 - Página no encontrada - ".htmlspecialchars($requestPath)."</h1>";
    echo "<p>La URL solicitada no existe.</p>";
    exit;
}

// ============================================
// 10. EJECUTAR MIDDLEWARES (si existen)
// ============================================

if (!empty($middleware)) {
    $middlewareClass = "App\\Middleware\\" . $middleware;
    
    if (class_exists($middlewareClass)) {
        $middlewareInstance = new $middlewareClass();
        
        if (method_exists($middlewareInstance, 'handle')) {
            $result = $middlewareInstance->handle();
            
            if ($result === false) {
                exit;
            }
        }
    } else {
        http_response_code(500);
        echo "<h1>500 - Error interno del servidor</h1>";
        echo "<p>Middleware no encontrado: {$middlewareClass}</p>";
        exit;
    }
}

// ============================================
// 11. CREAR EL CONTROLADOR
// ============================================

$controllerClass = "App\\Controllers\\" . $controller;

if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo "<h1>500 - Error interno del servidor</h1>";
    echo "<p>Controlador no encontrado: {$controllerClass}</p>";
    exit;
}

$controllerInstance = new $controllerClass();

if (!method_exists($controllerInstance, $method)) {
    http_response_code(500);
    echo "<h1>500 - Error interno del servidor</h1>";
    echo "<p>Método no encontrado: {$method} en {$controllerClass}</p>";
    exit;
}

// ============================================
// 12. EJECUTAR EL CONTROLADOR Y SU MÉTODO
// ============================================

$controllerInstance->$method(...$params);