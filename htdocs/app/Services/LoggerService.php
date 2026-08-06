<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Servicio de logs para auditoría de sentencias SQL
 * 
 * Registra todas las consultas SQL ejecutadas en la aplicación,
 * con información de contexto y rotación horaria de archivos.
 * 
 * Uso:
 *   LoggerService::log('SELECT * FROM usuarios', [], 'SELECT');
 */
class LoggerService
{
    private static ?LoggerService $instance = null;
    private string $logDir;
    private bool $enabled;
    private string $logLevel;
    private array $config;

    /**
     * Constructor privado (Singleton)
     */
    public function __construct(array $config = [])
    {
        // Configuración por defecto
        $this->config = array_merge([
            'enabled' => true,
            'log_dir' => '/logs/',
            'prefix' => 'sql_',
            'format' => 'Y-m-d_H',
            'timezone' => 'Europe/Madrid'
        ], $config);

        // Leer nivel de log desde .env
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'debug';
        $this->enabled = $this->logLevel !== 'none' && $this->config['enabled'];

        // ============================================
        // ✅ DETERMINAR LA RUTA ABSOLUTA DE LOGS
        // ============================================
        $this->logDir = $this->getLogDirectory();
        
        // Crear el directorio si no existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        // Establecer zona horaria
        date_default_timezone_set($this->config['timezone']);
    }

    /**
     * Obtiene el directorio de logs de forma segura
     * 
     * @return string Ruta absoluta del directorio de logs
     */
    private function getLogDirectory(): string
    {
        // ============================================
        // 🔥 MÉTODO DEFINITIVO: No usa file_exists() 
        //    para evitar problemas con open_basedir
        // ============================================
        
        // app/Services/LoggerService.php
        // subimos 2 niveles para llegar a la raíz del proyecto
        $projectRoot = dirname(__DIR__, 2);
        
        // Construir la ruta de logs
        $logPath = rtrim($projectRoot, '/') . '/logs/';
        
        return $logPath;
    }

    /**
     * Obtiene la instancia única del logger (Singleton)
     */
    public static function getInstance(array $config = []): LoggerService
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Método estático para registrar una consulta SQL fácilmente
     * 
     * @param string $sql Sentencia SQL
     * @param array $params Parámetros
     * @param string $type Tipo de consulta (SELECT, INSERT, UPDATE, DELETE, etc.)
     * @param float|null $executionTime Tiempo de ejecución en segundos
     */
    public static function log(
        string $sql,
        array $params = [],
        string $type = 'QUERY',
        ?float $executionTime = null
    ): void {
        try {
            $instance = self::getInstance();
            
            // Verificar si debe loguear según el nivel
            if (!$instance->shouldLog($type)) {
                return;
            }

            // Interpolar parámetros
            $interpolatedSql = self::interpolateParamsStatic($sql, $params);

            // Construir el registro
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => $type,
                'sql' => $interpolatedSql,
                'sql_raw' => $sql,
                'params' => $params,
                'execution_time' => $executionTime,
                'context' => self::buildContextStatic()
            ];

            // Escribir en el archivo
            $filename = $instance->config['prefix'] . date($instance->config['format']) . '.log';
            $filepath = $instance->logDir . $filename;

            $line = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($filepath, $line, FILE_APPEND | LOCK_EX);

        } catch (\Exception $e) {
            // Si falla el log, no interrumpir la ejecución
            error_log('Error en LoggerService::log: ' . $e->getMessage());
        }
    }

    /**
     * Registra un evento de autenticación en el log
     * 
     * @param string $message Mensaje descriptivo del evento
     * @param array $context Datos adicionales (IP, URI, API Key, etc.)
     * @param string $type Tipo de evento (AUTH_SUCCESS, AUTH_ERROR, etc.)
     */
    public static function logAuth(string $message, array $context = [], string $type = 'AUTH_INFO'): void
    {
        try {
            $instance = self::getInstance();
            
            // Verificar si debe loguear según el nivel
            if (!$instance->shouldLog($type)) {
                return;
            }

            // Construir el registro
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => $type,
                'message' => $message,
                'context' => array_merge(
                    self::buildContextStatic(),
                    $context
                )
            ];

            // Escribir en el archivo
            $filename = 'auth_' . date($instance->config['format']) . '.log';
            $filepath = $instance->logDir . $filename;

            $line = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($filepath, $line, FILE_APPEND | LOCK_EX);

        } catch (\Exception $e) {
            error_log('Error en LoggerService::logAuth: ' . $e->getMessage());
        }
    }

    /**
     * Determina si una consulta debe ser logueada según el nivel
     */
    private function shouldLog(string $type): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $logLevel = $this->logLevel;

        if ($logLevel === 'debug') {
            return true;
        }

        if ($logLevel === 'info') {
            return in_array($type, ['INSERT', 'UPDATE', 'DELETE', 'TRANSACTION']);
        }

        return false;
    }

    /**
     * Interpola los parámetros en la sentencia SQL (versión estática)
     */
    private static function interpolateParamsStatic(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        $result = $sql;

        foreach ($params as $key => $value) {
            if (is_null($value)) {
                $replacement = 'NULL';
            } elseif (is_int($value) || is_float($value)) {
                $replacement = (string)$value;
            } elseif (is_bool($value)) {
                $replacement = $value ? 'TRUE' : 'FALSE';
            } else {
                $replacement = "'" . str_replace("'", "''", (string)$value) . "'";
            }

            if (is_int($key) && $key >= 0) {
                $placeholder = '?';
            } else {
                $placeholder = strpos($key, ':') === 0 ? $key : ':' . $key;
            }

            if ($placeholder === '?') {
                $pos = strpos($result, '?');
                if ($pos !== false) {
                    $result = substr_replace($result, $replacement, $pos, 1);
                }
            } else {
                $result = str_replace($placeholder, $replacement, $result);
            }
        }

        return $result;
    }

    /**
     * Construye el contexto de la ejecución (versión estática)
     */
    private static function buildContextStatic(): array
    {
        $context = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'script' => $_SERVER['SCRIPT_NAME'] ?? 'CLI',
        ];

        if (isset($_SESSION['usuario_id'])) {
            $context['usuario_id'] = $_SESSION['usuario_id'];
            $context['usuario_nombre'] = $_SESSION['usuario_nombre'] ?? 'Desconocido';
            $context['usuario_rol'] = $_SESSION['usuario_rol'] ?? 'Usuario';
        }

        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $context['api_key'] = substr($_SERVER['HTTP_X_API_KEY'], 0, 10) . '...';
        }

        return $context;
    }

    /**
     * Verifica si el logger está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Obtiene el directorio de logs
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * Obtiene el archivo de log actual
     */
    public function getCurrentLogFile(): string
    {
        return $this->logDir . $this->config['prefix'] . date($this->config['format']) . '.log';
    }

    /**
     * Obtiene el nivel de log actual
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }
}