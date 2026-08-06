<?php
/**
 * ARCHIVO DE DIAGNÓSTICO DEL ENRUTADOR
 * 
 * Este archivo muestra toda la información de la petición para depurar
 * por qué el enrutador no está funcionando correctamente.
 * 
 * NO DEPENDE DE NINGÚN MODELO, BASE DE DATOS O MIDDLEWARE.
 */

// ============================================
// 1. CONFIGURACIÓN DE ERRORES (MOSTRAR TODO)
// ============================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 DIAGNÓSTICO DEL ENRUTADOR</h1>";
echo "<hr>";

// ============================================
// 2. INFORMACIÓN DE LA PETICIÓN (SERVER)
// ============================================
echo "<h2>📋 INFORMACIÓN DE LA PETICIÓN (SERVER)</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO DEFINIDO') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NO DEFINIDO') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NO DEFINIDO') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NO DEFINIDO') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NO DEFINIDO') . "\n";
echo "</pre>";

// ============================================
// 3. INFORMACIÓN DE HEADERS
// ============================================
echo "<h2>📋 HEADERS DE LA PETICIÓN</h2>";
echo "<pre>";
$headers = getallheaders();
foreach ($headers as $key => $value) {
    echo htmlspecialchars($key) . ": " . htmlspecialchars($value) . "\n";
}
echo "</pre>";

// ============================================
// 4. PROCESAR LA RUTA
// ============================================
echo "<h2>🛤️ PROCESAMIENTO DE LA RUTA</h2>";

// Obtener la URL solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

echo "<p><strong>URL completa:</strong> " . htmlspecialchars($requestUri) . "</p>";
echo "<p><strong>Ruta (path):</strong> " . htmlspecialchars($requestPath) . "</p>";

// Eliminar la barra inicial para normalizar
$requestPath = ltrim($requestPath, '/');

// Si la ruta está vacía, usar '/'
if ($requestPath === '') {
    $requestPath = '/';
}

echo "<p><strong>Ruta normalizada:</strong> " . htmlspecialchars($requestPath) . "</p>";

// ============================================
// 5. CARGAR LAS RUTAS DEL ARCHIVO routes.php
// ============================================
echo "<h2>📂 RUTAS DEFINIDAS EN routes.php</h2>";

// Verificar si existe el archivo de rutas
$routesFile = __DIR__ . '/../config/routes.php';
echo "<p><strong>Archivo de rutas:</strong> " . $routesFile . "</p>";

if (!file_exists($routesFile)) {
    echo "<p style='color:red;'>❌ ERROR: El archivo routes.php NO EXISTE en " . $routesFile . "</p>";
    exit;
}

$routes = require $routesFile;

echo "<pre>";
echo "Rutas definidas:\n";
print_r($routes);
echo "</pre>";

// ============================================
// 6. BUSCAR LA RUTA EN EL ARCHIVO DE RUTAS
// ============================================
echo "<h2>🔍 BÚSQUEDA DE LA RUTA</h2>";

$routeFound = false;
$controller = null;
$method = null;
$middleware = null;
$params = [];

foreach ($routes as $route => $handler) {
    // Convertir la ruta a expresión regular
    $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $route);
    $pattern = '#^' . $pattern . '$#';
    
    echo "<p><strong>Comparando:</strong> '" . htmlspecialchars($requestPath) . "' con patrón '" . htmlspecialchars($pattern) . "' (ruta: " . htmlspecialchars($route) . ")</p>";
    
    if (preg_match($pattern, $requestPath, $matches)) {
        $routeFound = true;
        $controller = $handler[0];
        $method = $handler[1] ?? 'index';
        $middleware = $handler[2] ?? '';
        
        // Extraer parámetros de la URL
        if (count($matches) > 1) {
            array_shift($matches);
            $params = $matches;
        }
        
        echo "<p style='color:green;'>✅ <strong>¡RUTA ENCONTRADA!</strong></p>";
        echo "<pre>";
        echo "Ruta: " . htmlspecialchars($route) . "\n";
        echo "Controlador: " . htmlspecialchars($controller) . "\n";
        echo "Método: " . htmlspecialchars($method) . "\n";
        echo "Middleware: " . htmlspecialchars($middleware) . "\n";
        echo "Parámetros: ";
        print_r($params);
        echo "</pre>";
        break;
    }
}

if (!$routeFound) {
    echo "<p style='color:red;'>❌ <strong>NO SE ENCONTRÓ NINGUNA RUTA</strong> para la URL solicitada.</p>";
}

// ============================================
// 7. INFORMACIÓN DEL SISTEMA
// ============================================
echo "<h2>🖥️ INFORMACIÓN DEL SISTEMA</h2>";
echo "<pre>";
echo "Versión de PHP: " . phpversion() . "\n";
echo "Directorio del script: " . __DIR__ . "\n";
echo "Directorio del proyecto: " . realpath(__DIR__ . '/../') . "\n";
echo "Usuario que ejecuta: " . (function_exists('get_current_user') ? get_current_user() : 'No disponible') . "\n";
echo "</pre>";

// ============================================
// 8. FIN DEL DIAGNÓSTICO
// ============================================
echo "<hr>";
echo "<p style='font-weight:bold;'>✅ DIAGNÓSTICO COMPLETADO. Revisa la información arriba para identificar el problema.</p>";