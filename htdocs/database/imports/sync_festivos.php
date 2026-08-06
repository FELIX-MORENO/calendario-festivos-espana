<?php
/**
 * SCRIPT DE SINCRONIZACIÓN Y LIMPIEZA DE DATOS
 * 
 * Descripción:
 *   - Borra (TRUNCATE) las tablas de datos maestros y los festivos.
 *   - Obtiene un archivo JSON con festivos de toda España desde la API externa.
 *   - Guarda una copia del JSON en logs/data_url.txt para depuración.
 *   - Normaliza los datos (comunidades, provincias, municipios).
 *   - Inserta o actualiza los datos en la base de datos usando el servicio SyncService.
 * 
 * Ubicación: database/imports/sync_festivos.php
 * Ejecución: php database/imports/sync_festivos.php
 * 
 * ADVERTENCIA: Este script BORRA los datos existentes en las tablas:
 *   - comunidades_autonomas
 *   - provincias
 *   - municipios
 *   - festivos
 * 
 *   Las tablas de usuarios y logs NO se ven afectadas.
 *   Se recomienda hacer una copia de seguridad antes de ejecutarlo.
 */

declare(strict_types=1);

// ============================================
// 1. CONFIGURACIÓN INICIAL
// ============================================

// Establecer el límite de tiempo de ejecución a 5 minutos (por si la API tarda)
set_time_limit(300);

// Mostrar errores en pantalla (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargar el autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno (.env)
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Configuración de la base de datos
$config = require_once __DIR__ . '/../../config/database.php';

echo "============================================\n";
echo "  SCRIPT DE SINCRONIZACIÓN DE FESTIVOS\n";
echo "  Fecha: " . date('d/m/Y H:i:s') . "\n";
echo "============================================\n\n";

// ============================================
// 2. CONEXIÓN A LA BASE DE DATOS
// ============================================

try {
    $dsn = sprintf(
        "%s:host=%s;port=%s;dbname=%s;charset=%s",
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "[OK] Conexión a la base de datos establecida.\n\n";

} catch (PDOException $e) {
    die("[ERROR] No se pudo conectar a la base de datos: " . $e->getMessage() . "\n");
}

// ============================================
// 3. LIMPIEZA DE DATOS (TRUNCATE)
// ============================================

echo "[INFO] Iniciando limpieza de tablas...\n";

try {
    // Desactivar restricciones de clave foránea para poder truncar
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Lista de tablas a limpiar (en orden inverso para evitar conflictos FK)
    $tables = [
        'festivos',
        'municipios',
        'provincias',
        'comunidades_autonomas'
    ];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE {$table}");
        echo "  - Tabla '{$table}' vaciada correctamente.\n";
    }

    // Reactivar restricciones
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "[OK] Limpieza completada.\n\n";

} catch (PDOException $e) {
    die("[ERROR] Fallo al limpiar las tablas: " . $e->getMessage() . "\n");
}

// ============================================
// 4. OBTENER DATOS DE LA API EXTERNA
// ============================================

echo "[INFO] Obteniendo datos de la API externa...\n";

$url = 'https://calendariosnacionales.com/es/v1/2026/todo.json';

try {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar solo en desarrollo local

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('Error en cURL: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("La API devolvió el código HTTP: {$httpCode}");
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    echo "[OK] Datos obtenidos correctamente ({$httpCode} OK).\n";
    echo "  - Tamaño del JSON: " . round(strlen($response) / 1024, 2) . " KB\n\n";

    // ============================================
    // GUARDAR COPIA DEL JSON EN logs/data_url.txt
    // ============================================
    
    // Asegurar que la carpeta logs existe
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/data_url.txt';
    
    // Guardar el JSON con formato legible (pretty print)
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Añadir cabecera con información de fecha y URL
    $header = "============================================\n";
    $header .= "  DATOS OBTENIDOS DE LA API EXTERNA\n";
    $header .= "  URL: {$url}\n";
    $header .= "  Fecha de obtención: " . date('d/m/Y H:i:s') . "\n";
    $header .= "  Tamaño del JSON: " . round(strlen($response) / 1024, 2) . " KB\n";
    $header .= "============================================\n\n";
    
    $content = $header . $jsonContent;
    
    if (file_put_contents($logFile, $content) !== false) {
        echo "[OK] Copia del JSON guardada en: logs/data_url.txt\n";
        echo "  - Tamaño del archivo: " . round(filesize($logFile) / 1024, 2) . " KB\n\n";
    } else {
        echo "[WARNING] No se pudo guardar el archivo logs/data_url.txt\n\n";
    }

} catch (Exception $e) {
    die("[ERROR] Fallo al obtener los datos: " . $e->getMessage() . "\n");
}

// ============================================
// 5. PROCESAR E INSERTAR DATOS (USANDO SyncService)
// ============================================

echo "[INFO] Procesando e insertando datos en la base de datos...\n";

try {
    // Crear el servicio de sincronización con debug ACTIVADO
    $syncService = new App\Services\SyncService($pdo, $data, 2026, true);

    // Ejecutar la sincronización
    $syncService->sync();

    echo "\n[OK] Datos insertados correctamente.\n\n";

} catch (Exception $e) {
    die("[ERROR] Falló el proceso: " . $e->getMessage() . "\n");
}

// ============================================
// 6. RESUMEN FINAL
// ============================================

echo "============================================\n";
echo "  [OK] PROCESO COMPLETADO CON ÉXITO\n";
echo "  Fecha de finalización: " . date('d/m/Y H:i:s') . "\n";
echo "  Log guardado en: logs/data_url.txt\n";
echo "============================================\n";