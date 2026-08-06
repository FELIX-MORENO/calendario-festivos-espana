<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use App\Services\LoggerService;

/**
 * Clase base para todos los modelos de la aplicación
 * 
 * Proporciona la conexión a la base de datos y métodos genéricos
 * para operaciones CRUD básicas con sentencias preparadas.
 * 
 * Integra el sistema de logs para auditoría de sentencias SQL.
 */
abstract class Model
{
    /**
     * Instancia de PDO (conexión a la base de datos)
     */
    protected PDO $db;

    /**
     * Nombre de la tabla asociada al modelo
     * Debe ser definido en cada modelo hijo
     */
    protected string $table = '';

    /**
     * Servicio de logs
     */
    private ?LoggerService $logger = null;

    /**
     * Nivel de log configurado
     */
    private string $logLevel;

    /**
     * Constructor: Inicializa la conexión a la base de datos y el logger
     * 
     * @throws PDOException Si la conexión falla
     */
    public function __construct()
    {
        $this->connect();
        $this->initLogger();
    }

    /**
     * Inicializa el servicio de logs
     */
    private function initLogger(): void
    {
        try {
            // Leer nivel de log desde .env
            $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'info';
            
            // Si LOG_LEVEL es 'none', desactivar logs
            $enabled = $this->logLevel !== 'none';

            // Configuración del logger
            $logConfig = [
                'enabled' => $enabled,
                'log_dir' => $_ENV['LOG_DIR'] ?? __DIR__ . '/../../logs/',
                'prefix' => 'sql_',
                'format' => 'Y-m-d_H',
                'timezone' => 'Europe/Madrid'
            ];

            $this->logger = new LoggerService($logConfig);
        } catch (\Exception $e) {
            // Si falla el logger, continuar sin logs
            error_log('Error al inicializar LoggerService: ' . $e->getMessage());
            $this->logger = null;
        }
    }

    /**
     * Determina si una consulta debe ser logueada según el nivel
     * 
     * @param string $type Tipo de consulta (SELECT, INSERT, UPDATE, DELETE, etc.)
     * @return bool True si debe loguearse
     */
    private function shouldLog(string $type): bool
    {
        if (!$this->logger || !$this->logger->isEnabled()) {
            return false;
        }

        $logLevel = $this->logLevel;

        // debug: loguea todo
        if ($logLevel === 'debug') {
            return true;
        }

        // info: loguea solo modificaciones (INSERT, UPDATE, DELETE)
        if ($logLevel === 'info') {
            return in_array($type, ['INSERT', 'UPDATE', 'DELETE', 'TRANSACTION']);
        }

        // warning: solo errores (no se usa aquí)
        // error: solo errores críticos (no se usa aquí)
        return false;
    }

    /**
     * Registra una consulta SQL en el log
     * 
     * @param string $sql Sentencia SQL
     * @param array $params Parámetros
     * @param string $type Tipo de consulta
     * @param float|null $executionTime Tiempo de ejecución
     */
    protected function logQuery(string $sql, array $params = [], string $type = 'QUERY', ?float $executionTime = null): void
    {
        if ($this->shouldLog($type)) {
            //$this->logger->logSql($sql, $params, $type, $executionTime);
            // ✅ Usar el método estático log() de LoggerService
            LoggerService::log($sql, $params, $type, $executionTime);            
        }
    }

    /**
     * Registra una sentencia PDO en el log
     * 
     * @param \PDOStatement $stmt Sentencia PDO
     * @param array $params Parámetros
     * @param string $type Tipo de consulta
     * @param float|null $executionTime Tiempo de ejecución
     */
    protected function logStatement(\PDOStatement $stmt, array $params = [], string $type = 'QUERY', ?float $executionTime = null): void
    {
        if ($this->shouldLog($type)) {
           // $this->logger->logStatement($stmt, $params, $type, $executionTime);
            // ✅ Usar el método estático log() de LoggerService
            LoggerService::log($stmt->queryString, $params, $type, $executionTime);
        }
    }

    /**
     * Establece la conexión a la base de datos usando PDO
     * 
     * @throws PDOException Si la conexión falla
     */
    private function connect(): void
    {
        try {
            // Cargar configuración desde el archivo de configuración
            $configFile = __DIR__ . '/../../config/database.php';
            
            if (!file_exists($configFile)) {
                throw new PDOException("Archivo de configuración no encontrado: {$configFile}");
            }
            
            $config = require($configFile);
            
            if (!is_array($config)) {
                throw new PDOException("La configuración de la base de datos no es válida (no devuelve un array)");
            }

            // Verificar que las claves necesarias existen
            $requiredKeys = ['driver', 'host', 'port', 'database', 'username', 'password', 'charset'];
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key])) {
                    throw new PDOException("Falta la clave '{$key}' en la configuración de la base de datos");
                }
            }

            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            $this->db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            // Establecer el modo de error a excepciones
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {
            // Registrar el error en el log
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            
            // Lanzar la excepción para que el controlador la maneje
            throw new PDOException("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la conexión PDO
     * 
     * @return PDO
     */
    public function getDb(): PDO
    {
        return $this->db;
    }

    /**
     * Obtiene todos los registros activos de la tabla
     * 
     * @param string $orderBy Campo para ordenar (opcional)
     * @param string $order Dirección de orden (ASC o DESC)
     * @return array Lista de registros
     */
    public function getAll(string $orderBy = 'id', string $order = 'ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY {$orderBy} {$order}";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [], 'SELECT', $executionTime);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro por su ID
     * 
     * @param int $id ID del registro
     * @return array|null Datos del registro o null si no existe
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND activo = 1";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [':id' => $id], 'SELECT', $executionTime);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Inserta un nuevo registro en la tabla
     * 
     * @param array $data Datos a insertar (clave = nombre del campo)
     * @return int ID del registro insertado
     * @throws PDOException
     */
    public function insert(array $data): int
    {
        // Filtrar solo los campos que existen en la tabla
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES ({$placeholders})";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        
        // Vincular parámetros con sus tipos
        foreach ($data as $key => $value) {
            $type = $this->getPdoType($value);
            $stmt->bindValue(":{$key}", $value, $type);
        }
        
        $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, $data, 'INSERT', $executionTime);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un registro existente
     * 
     * @param int $id ID del registro a actualizar
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     * @throws PDOException
     */
    public function update(int $id, array $data): bool
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        
        $sql = "UPDATE {$this->table} 
                SET " . implode(', ', $setClause) . " 
                WHERE id = :id";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        
        // Vincular parámetros
        foreach ($data as $key => $value) {
            $type = $this->getPdoType($value);
            $stmt->bindValue(":{$key}", $value, $type);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        $result = $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $logData = array_merge($data, [':id' => $id]);
        $this->logQuery($sql, $logData, 'UPDATE', $executionTime);
        
        return $result;
    }

    /**
     * Eliminación lógica (activo = 0)
     * 
     * @param int $id ID del registro
     * @param int $modificadoPor ID del usuario que elimina
     * @return bool True si se eliminó correctamente
     */
    public function softDelete(int $id, int $modificadoPor): bool
    {
        $sql = "UPDATE {$this->table} 
                SET activo = 0, 
                    modificado_por = :modificado_por, 
                    modificado_en = NOW() 
                WHERE id = :id";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':modificado_por', $modificadoPor, PDO::PARAM_INT);
        $result = $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [':id' => $id, ':modificado_por' => $modificadoPor], 'UPDATE (SOFT DELETE)', $executionTime);
        
        return $result;
    }

    /**
     * Reactiva un registro (activo = 1)
     * 
     * @param int $id ID del registro
     * @param int $modificadoPor ID del usuario que reactiva
     * @return bool True si se reactivó correctamente
     */
    public function reactivate(int $id, int $modificadoPor): bool
    {
        $sql = "UPDATE {$this->table} 
                SET activo = 1, 
                    modificado_por = :modificado_por, 
                    modificado_en = NOW() 
                WHERE id = :id";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':modificado_por', $modificadoPor, PDO::PARAM_INT);
        $result = $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [':id' => $id, ':modificado_por' => $modificadoPor], 'UPDATE (REACTIVATE)', $executionTime);
        
        return $result;
    }

    /**
     * Obtiene todos los registros (incluyendo inactivos)
     * 
     * @return array Lista de todos los registros
     */
    public function getAllIncludingInactive(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [], 'SELECT', $executionTime);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el número de registros activos
     * 
     * @return int Número de registros activos
     */
    public function countActive(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE activo = 1";
        
        $startTime = microtime(true);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $executionTime = microtime(true) - $startTime;
        
        $this->logQuery($sql, [], 'SELECT (COUNT)', $executionTime);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Determina el tipo PDO para un valor
     * 
     * @param mixed $value Valor a evaluar
     * @return int Tipo PDO (PDO::PARAM_*)
     */
    private function getPdoType($value): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    /**
     * Inicia una transacción
     * 
     * @return bool True si se inició correctamente
     */
    public function beginTransaction(): bool
    {
        $this->logQuery('BEGIN TRANSACTION', [], 'TRANSACTION');
        return $this->db->beginTransaction();
    }

    /**
     * Confirma una transacción
     * 
     * @return bool True si se confirmó correctamente
     */
    public function commit(): bool
    {
        $this->logQuery('COMMIT', [], 'TRANSACTION');
        return $this->db->commit();
    }

    /**
     * Revierte una transacción
     * 
     * @return bool True si se revirtió correctamente
     */
    public function rollback(): bool
    {
        $this->logQuery('ROLLBACK', [], 'TRANSACTION');
        return $this->db->rollBack();
    }

    /**
     * Obtiene el ID de la última inserción
     * 
     * @return int Último ID insertado
     */
    public function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}